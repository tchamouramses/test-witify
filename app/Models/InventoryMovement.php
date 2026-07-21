<?php

namespace App\Models;

use Database\Factories\InventoryMovementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A single stock movement (receiving, adjustment, ...). The journal is the
 * full history of the inventory: the current quantity of a product in a
 * warehouse (inventory_balances) must always equal the sum of its movements.
 *
 * @property int $id
 * @property int $product_id
 * @property int $warehouse_id
 * @property int|null $user_id
 * @property int $quantity_change
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['product_id', 'warehouse_id', 'user_id', 'quantity_change'])]
class InventoryMovement extends Model
{
    /** @use HasFactory<InventoryMovementFactory> */
    use HasFactory;

    /**
     * The quantity currently in stock for a product in a warehouse.
     */
    public static function currentStock(int $productId, int $warehouseId): int
    {
        return (int) static::query()
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->sum('quantity_change');
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
