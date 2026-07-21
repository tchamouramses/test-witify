<?php

namespace App\Actions;

use App\Models\Order;
use App\Models\OrderLine;
use Illuminate\Pagination\LengthAwarePaginator;

class ListOrders implements Action
{
    private const int ITEMS_PER_PAGE = 12;

    /**
     * A page of orders with their line count and total.
     *
     * @return LengthAwarePaginator<int, array{id: int, number: string, customer_name: string, status: string, lines_count: int, total: float}>
     */
    public function handle(): LengthAwarePaginator
    {
        $orderTable = (new Order)->getTable();

        return Order::query()
            ->select(['id', 'number', 'customer_name', 'status', 'created_at'])
            ->withCount('lines')
            ->addSelect([
                'total' => OrderLine::query()
                    ->selectRaw('COALESCE(SUM(quantity_ordered * unit_price), 0)')
                    ->whereColumn('order_id', "{$orderTable}.id"),
            ])
            ->latest()
            ->paginate(self::ITEMS_PER_PAGE)
            ->through(fn (Order $order): array => [
                'id' => $order->id,
                'number' => $order->number,
                'customer_name' => $order->customer_name,
                'status' => $order->status->value,
                'lines_count' => (int) $order->lines_count,
                'total' => (float) $order->total,
            ]);
    }
}
