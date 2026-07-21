<?php

namespace App\Actions;

use App\Models\InventoryMovement;
use App\Models\InventoryReservation;
use App\Models\OrderLine;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use stdClass;

class GetInventoryOverview implements Action
{
    private const int ITEMS_PER_PAGE = 12;

    /**
     * A page of product availability across every warehouse, calculated from
     * the movement journal after subtracting existing reservations.
     *
     * @return array{
     *     warehouses: Collection<int, array{id: int, name: string, code: string}>,
     *     products: LengthAwarePaginator<int, array{id: int, name: string, sku: string, quantities: array<int, int>}>,
     * }
     */
    public function handle(): array
    {
        $products = Product::query()
            ->select(['id', 'name', 'sku'])
            ->orderBy('name')
            ->paginate(self::ITEMS_PER_PAGE);
        $availableQuantities = $this->availableQuantities(
            $products->getCollection()->pluck('id'),
        );

        $products->through(fn (Product $product): array => [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'quantities' => $availableQuantities[$product->id] ?? [],
        ]);

        $warehouses = Warehouse::query()
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn (Warehouse $warehouse): array => [
                'id' => $warehouse->id,
                'name' => $warehouse->name,
                'code' => $warehouse->code,
            ]);

        return [
            'warehouses' => $warehouses,
            'products' => $products,
        ];
    }

    /**
     * @param  Collection<int, int>  $productIds
     * @return array<int, array<int, int>>
     */
    private function availableQuantities(Collection $productIds): array
    {
        $quantities = [];

        $movementTotals = InventoryMovement::query()
            ->whereIn('product_id', $productIds)
            ->select(['product_id', 'warehouse_id'])
            ->selectRaw('SUM(quantity_change) AS physical_quantity')
            ->groupBy('product_id', 'warehouse_id')
            ->get();

        foreach ($movementTotals as $total) {
            $quantities[$total->product_id][$total->warehouse_id] = (int) $total->getAttribute('physical_quantity');
        }

        foreach ($this->reservationTotals($productIds) as $total) {
            $productId = (int) $total->product_id;
            $warehouseId = (int) $total->warehouse_id;
            $physicalQuantity = $quantities[$productId][$warehouseId] ?? 0;
            $quantities[$productId][$warehouseId] = max(
                0,
                $physicalQuantity - (int) $total->reserved_quantity,
            );
        }

        return $quantities;
    }

    /**
     * @param  Collection<int, int>  $productIds
     * @return Collection<int, stdClass>
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
            ->selectRaw('SUM(reservations.quantity) AS reserved_quantity')
            ->groupBy(
                'reservation_order_lines.product_id',
                'reservations.warehouse_id',
            )
            ->get();
    }
}
