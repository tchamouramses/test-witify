<?php

namespace App\Actions;

use App\Models\InventoryBalance;
use App\Models\InventoryMovement;
use App\Models\InventoryReservation;
use App\Models\OrderLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdjustInventory implements Action
{
    /**
     * Adjust the stock of a product in a warehouse.
     *
     * The movement journal is the source of truth. Adjustments for the same
     * product and warehouse are serialized by locking their balance before a
     * movement is recorded. The current quantity is calculated from the
     * journal, then the resulting quantity is projected onto the balance in
     * the same transaction.
     *
     * @throws ValidationException when the adjustment would make the stock negative
     */
    public function handle(int $productId, int $warehouseId, int $quantityChange, ?int $userId = null): InventoryMovement
    {
        return DB::transaction(function () use ($productId, $warehouseId, $quantityChange, $userId): InventoryMovement {
            InventoryBalance::query()->upsert(
                [
                    'product_id' => $productId,
                    'warehouse_id' => $warehouseId,
                    'quantity' => 0,
                ],
                uniqueBy: ['product_id', 'warehouse_id'],
                update: ['updated_at'],
            );

            $balance = InventoryBalance::query()
                ->where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->firstOrFail();

            $currentQuantity = InventoryMovement::currentStock($productId, $warehouseId);
            $newQuantity = $currentQuantity + $quantityChange;

            if ($newQuantity < 0) {
                throw ValidationException::withMessages([
                    'quantity_change' => __('Only :quantity units are in stock in this warehouse.', [
                        'quantity' => $currentQuantity,
                    ]),
                ]);
            }

            $quantityReserved = (int) InventoryReservation::query()
                ->where('warehouse_id', $warehouseId)
                ->whereIn(
                    'order_line_id',
                    OrderLine::query()
                        ->select('id')
                        ->where('product_id', $productId),
                )
                ->sum('quantity');

            if ($newQuantity < $quantityReserved) {
                throw ValidationException::withMessages([
                    'quantity_change' => __('This adjustment would reduce stock below the :quantity units already reserved.', [
                        'quantity' => $quantityReserved,
                    ]),
                ]);
            }

            $movement = InventoryMovement::create([
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'user_id' => $userId,
                'quantity_change' => $quantityChange,
            ]);

            $balance->update(['quantity' => $newQuantity]);

            return $movement;
        }, attempts: 3);
    }
}
