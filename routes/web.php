<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryAdjustmentController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InventoryReservationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('orders/{order}/lines/{line}/reservations', [InventoryReservationController::class, 'store'])
        ->scopeBindings()
        ->name('orders.lines.reservations.store');

    Route::get('warehouses', [WarehouseController::class, 'index'])->name('warehouses.index');

    Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::post('inventory/adjustments', [InventoryAdjustmentController::class, 'store'])
        ->name('inventory.adjustments.store');
});

require __DIR__.'/settings.php';
