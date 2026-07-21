<?php

namespace App\Actions;

use App\Models\InventoryBalance;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use stdClass;

class GetDashboardOverview implements Action
{
    private const int LOW_STOCK_THRESHOLD = 5;

    /**
     * @return array{
     *     summary: array{physical_stock: int, reserved_stock: int, available_stock: int, orders_count: int, products_count: int, warehouses_count: int},
     *     fulfillment: array{ordered_units: int, reserved_units: int, pending_units: int, rate: float},
     *     warehouses: array<int, array{id: int, name: string, code: string, physical_stock: int, reserved_stock: int, available_stock: int, reserved_percentage: float}>,
     *     recent_orders: array<int, array{id: int, number: string, customer_name: string, status: string, lines_count: int, total: float, ordered_units: int, reserved_units: int, fulfillment_rate: float, created_at: string}>,
     *     inventory_alerts: array<int, array{id: int, name: string, sku: string, physical_stock: int, reserved_stock: int, available_stock: int, severity: string}>,
     * }
     */
    public function handle(): array
    {
        $totals = $this->totals();
        $physicalStock = (int) $totals->physical_stock;
        $reservedStock = (int) $totals->reserved_stock;
        $orderedUnits = (int) $totals->ordered_units;

        return [
            'summary' => [
                'physical_stock' => $physicalStock,
                'reserved_stock' => $reservedStock,
                'available_stock' => max(0, $physicalStock - $reservedStock),
                'orders_count' => (int) $totals->orders_count,
                'products_count' => (int) $totals->products_count,
                'warehouses_count' => (int) $totals->warehouses_count,
            ],
            'fulfillment' => [
                'ordered_units' => $orderedUnits,
                'reserved_units' => $reservedStock,
                'pending_units' => max(0, $orderedUnits - $reservedStock),
                'rate' => $this->percentage($reservedStock, $orderedUnits),
            ],
            'warehouses' => $this->warehouses(),
            'recent_orders' => $this->recentOrders(),
            'inventory_alerts' => $this->inventoryAlerts(),
        ];
    }

    private function totals(): stdClass
    {
        /** @var stdClass|null $totals */
        $totals = DB::query()
            ->selectSub(
                InventoryBalance::query()->selectRaw('COALESCE(SUM(quantity), 0)'),
                'physical_stock',
            )
            ->selectSub(
                InventoryReservation::query()->selectRaw('COALESCE(SUM(quantity), 0)'),
                'reserved_stock',
            )
            ->selectSub(
                OrderLine::query()->selectRaw('COALESCE(SUM(quantity_ordered), 0)'),
                'ordered_units',
            )
            ->selectSub(Order::query()->selectRaw('COUNT(*)'), 'orders_count')
            ->selectSub(Product::query()->selectRaw('COUNT(*)'), 'products_count')
            ->selectSub(Warehouse::query()->selectRaw('COUNT(*)'), 'warehouses_count')
            ->first();

        return $totals ?? new stdClass;
    }

    /**
     * @return array<int, array{id: int, name: string, code: string, physical_stock: int, reserved_stock: int, available_stock: int, reserved_percentage: float}>
     */
    private function warehouses(): array
    {
        return Warehouse::query()
            ->select(['id', 'name', 'code'])
            ->withSum('inventoryBalances as physical_stock', 'quantity')
            ->withSum('inventoryReservations as reserved_stock', 'quantity')
            ->orderByDesc('physical_stock')
            ->get()
            ->map(function (Warehouse $warehouse): array {
                $physicalStock = (int) $warehouse->getAttribute('physical_stock');
                $reservedStock = (int) $warehouse->getAttribute('reserved_stock');

                return [
                    'id' => $warehouse->id,
                    'name' => $warehouse->name,
                    'code' => $warehouse->code,
                    'physical_stock' => $physicalStock,
                    'reserved_stock' => $reservedStock,
                    'available_stock' => max(0, $physicalStock - $reservedStock),
                    'reserved_percentage' => $this->percentage($reservedStock, $physicalStock),
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array{id: int, number: string, customer_name: string, status: string, lines_count: int, total: float, ordered_units: int, reserved_units: int, fulfillment_rate: float, created_at: string}>
     */
    private function recentOrders(): array
    {
        return Order::query()
            ->with([
                'lines:id,order_id,quantity_ordered,unit_price',
                'lines.reservations:id,order_line_id,quantity',
            ])
            ->latest()
            ->limit(5)
            ->get(['id', 'number', 'customer_name', 'status', 'created_at'])
            ->map(function (Order $order): array {
                $orderedUnits = (int) $order->lines->sum('quantity_ordered');
                $reservedUnits = (int) $order->lines->sum(
                    fn (OrderLine $line): int => (int) $line->reservations->sum('quantity'),
                );
                $total = (float) $order->lines->sum(
                    fn (OrderLine $line): float => $line->quantity_ordered * (float) $line->unit_price,
                );

                return [
                    'id' => $order->id,
                    'number' => $order->number,
                    'customer_name' => $order->customer_name,
                    'status' => $order->status->value,
                    'lines_count' => $order->lines->count(),
                    'total' => round($total, 2),
                    'ordered_units' => $orderedUnits,
                    'reserved_units' => $reservedUnits,
                    'fulfillment_rate' => $this->percentage($reservedUnits, $orderedUnits),
                    'created_at' => $order->created_at?->toISOString() ?? '',
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array{id: int, name: string, sku: string, physical_stock: int, reserved_stock: int, available_stock: int, severity: string}>
     */
    private function inventoryAlerts(): array
    {
        $productTable = (new Product)->getTable();
        $balanceTable = (new InventoryBalance)->getTable();
        $reservationTable = (new InventoryReservation)->getTable();
        $orderLineTable = (new OrderLine)->getTable();

        $balanceTotals = InventoryBalance::query()
            ->from("{$balanceTable} as alert_balances")
            ->toBase()
            ->select('alert_balances.product_id')
            ->selectRaw('SUM(alert_balances.quantity) AS physical_stock')
            ->groupBy('alert_balances.product_id');
        $reservationTotals = InventoryReservation::query()
            ->from("{$reservationTable} as alert_reservations")
            ->toBase()
            ->join(
                "{$orderLineTable} as alert_order_lines",
                'alert_order_lines.id',
                '=',
                'alert_reservations.order_line_id',
            )
            ->select('alert_order_lines.product_id')
            ->selectRaw('SUM(alert_reservations.quantity) AS reserved_stock')
            ->groupBy('alert_order_lines.product_id');

        return Product::query()
            ->from("{$productTable} as alert_products")
            ->leftJoinSub($balanceTotals, 'alert_balance_totals', function ($join): void {
                $join->on('alert_balance_totals.product_id', '=', 'alert_products.id');
            })
            ->leftJoinSub($reservationTotals, 'alert_reservation_totals', function ($join): void {
                $join->on('alert_reservation_totals.product_id', '=', 'alert_products.id');
            })
            ->select([
                'alert_products.id',
                'alert_products.name',
                'alert_products.sku',
            ])
            ->selectRaw('COALESCE(alert_balance_totals.physical_stock, 0) AS physical_stock')
            ->selectRaw('COALESCE(alert_reservation_totals.reserved_stock, 0) AS reserved_stock')
            ->selectRaw('GREATEST(COALESCE(alert_balance_totals.physical_stock, 0) - COALESCE(alert_reservation_totals.reserved_stock, 0), 0) AS available_stock')
            ->whereRaw('COALESCE(alert_balance_totals.physical_stock, 0) - COALESCE(alert_reservation_totals.reserved_stock, 0) <= ?', [self::LOW_STOCK_THRESHOLD])
            ->orderBy('available_stock')
            ->orderBy('alert_products.name')
            ->limit(5)
            ->get()
            ->map(function (Product $product): array {
                $physicalStock = (int) $product->getAttribute('physical_stock');
                $reservedStock = (int) $product->getAttribute('reserved_stock');
                $availableStock = (int) $product->getAttribute('available_stock');

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'physical_stock' => $physicalStock,
                    'reserved_stock' => $reservedStock,
                    'available_stock' => $availableStock,
                    'severity' => $availableStock === 0 ? 'critical' : 'low',
                ];
            })
            ->all();
    }

    private function percentage(int $part, int $whole): float
    {
        if ($whole === 0) {
            return 0.0;
        }

        return round(min(100, ($part / $whole) * 100), 1);
    }
}
