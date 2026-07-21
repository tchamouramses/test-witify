<?php

namespace App\Actions;

use App\Models\InventoryBalance;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Warehouse;
use Illuminate\Support\Collection;
use stdClass;

class GetOrderDetails implements Action
{
    /**
     * An order with its lines, ready for the order page.
     *
     * @return array<string, mixed>
     */
    public function handle(Order $order): array
    {
        $order->load(['lines.product', 'lines.reservations']);

        $warehouses = Warehouse::query()
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
        $productIds = $order->lines
            ->pluck('product_id')
            ->unique()
            ->values();
        $balancesByProductAndWarehouse = InventoryBalance::query()
            ->whereIn('product_id', $productIds)
            ->get(['product_id', 'warehouse_id', 'quantity'])
            ->mapWithKeys(fn (InventoryBalance $balance): array => [
                $this->inventoryKey($balance->product_id, $balance->warehouse_id) => $balance->quantity,
            ]);
        $reservationsByProductAndWarehouse = $this->reservationTotals($productIds);

        $lines = $order->lines->map(function (OrderLine $line) use (
            $warehouses,
            $balancesByProductAndWarehouse,
            $reservationsByProductAndWarehouse,
        ): array {
            $quantityReserved = (int) $line->reservations->sum('quantity');

            return [
                'id' => $line->id,
                'product' => [
                    'id' => $line->product->id,
                    'name' => $line->product->name,
                    'sku' => $line->product->sku,
                ],
                'quantity_ordered' => $line->quantity_ordered,
                'quantity_reserved' => $quantityReserved,
                'quantity_remaining' => max(0, $line->quantity_ordered - $quantityReserved),
                'warehouses' => $warehouses->map(function (Warehouse $warehouse) use (
                    $line,
                    $balancesByProductAndWarehouse,
                    $reservationsByProductAndWarehouse,
                ): array {
                    $inventoryKey = $this->inventoryKey($line->product_id, $warehouse->id);
                    $physicalQuantity = (int) $balancesByProductAndWarehouse->get($inventoryKey, 0);
                    $reservedQuantity = (int) $reservationsByProductAndWarehouse->get($inventoryKey, 0);

                    return [
                        'id' => $warehouse->id,
                        'name' => $warehouse->name,
                        'code' => $warehouse->code,
                        'available_quantity' => max(0, $physicalQuantity - $reservedQuantity),
                    ];
                }),
                'unit_price' => (float) $line->unit_price,
                'line_total' => round($line->quantity_ordered * (float) $line->unit_price, 2),
            ];
        });

        return [
            'id' => $order->id,
            'number' => $order->number,
            'customer_name' => $order->customer_name,
            'status' => $order->status->value,
            'lines' => $lines,
            'total' => round($lines->sum('line_total'), 2),
        ];
    }

    /**
     * @param  Collection<int, int>  $productIds
     * @return Collection<string, int>
     */
    private function reservationTotals(Collection $productIds): Collection
    {
        $reservationTable = (new InventoryReservation)->getTable();
        $orderLineTable = (new OrderLine)->getTable();

        return InventoryReservation::query()
            ->from("{$reservationTable} as reservations")
            ->toBase()
            ->join(
                "{$orderLineTable} as reservation_order_lines",
                'reservation_order_lines.id',
                '=',
                'reservations.order_line_id',
            )
            ->whereIn('reservation_order_lines.product_id', $productIds)
            ->select([
                'reservation_order_lines.product_id',
                'reservations.warehouse_id',
            ])
            ->selectRaw('SUM(reservations.quantity) AS quantity_reserved')
            ->groupBy(
                'reservation_order_lines.product_id',
                'reservations.warehouse_id',
            )
            ->get()
            ->mapWithKeys(fn (stdClass $total): array => [
                $this->inventoryKey((int) $total->product_id, (int) $total->warehouse_id) => (int) $total->quantity_reserved,
            ]);
    }

    private function inventoryKey(int $productId, int $warehouseId): string
    {
        return "{$productId}:{$warehouseId}";
    }
}
