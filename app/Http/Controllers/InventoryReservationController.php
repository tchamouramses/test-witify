<?php

namespace App\Http\Controllers;

use App\Actions\ReserveInventory;
use App\Http\Requests\StoreInventoryReservationRequest;
use App\Models\Order;
use App\Models\OrderLine;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class InventoryReservationController extends Controller
{
    public function store(
        StoreInventoryReservationRequest $request,
        Order $order,
        OrderLine $line,
        ReserveInventory $reserveInventory,
    ): RedirectResponse {
        $validated = $request->validated();

        $reserveInventory->handle(
            orderLine: $line,
            warehouseId: (int) $validated['warehouse_id'],
            quantity: (int) $validated['quantity'],
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Inventory reserved.'),
        ]);

        return back();
    }
}
