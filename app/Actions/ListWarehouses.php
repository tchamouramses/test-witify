<?php

namespace App\Actions;

use App\Models\Warehouse;
use Illuminate\Pagination\LengthAwarePaginator;

class ListWarehouses implements Action
{
    private const int ITEMS_PER_PAGE = 12;

    /**
     * A page of warehouses with how many products they hold and their total
     * units in stock.
     *
     * @return LengthAwarePaginator<int, array{id: int, name: string, code: string, products_in_stock: int, units_in_stock: int}>
     */
    public function handle(): LengthAwarePaginator
    {
        return Warehouse::query()
            ->select(['id', 'name', 'code'])
            ->withCount([
                'inventoryBalances as products_in_stock' => fn ($query) => $query->where('quantity', '>', 0),
            ])
            ->withSum('inventoryBalances as units_in_stock', 'quantity')
            ->orderBy('name')
            ->paginate(self::ITEMS_PER_PAGE)
            ->through(fn (Warehouse $warehouse): array => [
                'id' => $warehouse->id,
                'name' => $warehouse->name,
                'code' => $warehouse->code,
                'products_in_stock' => (int) $warehouse->getAttribute('products_in_stock'),
                'units_in_stock' => (int) $warehouse->getAttribute('units_in_stock'),
            ]);
    }
}
