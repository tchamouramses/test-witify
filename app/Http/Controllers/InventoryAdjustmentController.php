<?php

namespace App\Http\Controllers;

use App\Actions\AdjustInventory;
use App\Http\Requests\StoreInventoryAdjustmentRequest;
use Illuminate\Http\RedirectResponse;

class InventoryAdjustmentController extends Controller
{
    public function store(StoreInventoryAdjustmentRequest $request, AdjustInventory $adjustInventory): RedirectResponse
    {
        $validated = $request->validated();

        $adjustInventory->handle(
            productId: (int) $validated['product_id'],
            warehouseId: (int) $validated['warehouse_id'],
            quantityChange: (int) $validated['quantity_change'],
            userId: $request->user()?->id,
        );

        return back();
    }
}
