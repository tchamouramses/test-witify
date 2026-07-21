<?php

use App\Models\InventoryBalance;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->where('name', 'Linvara')
    );
});

test('dashboard presents actionable inventory and order metrics', function () {
    $user = User::factory()->create();
    $primaryWarehouse = Warehouse::factory()->create([
        'name' => 'Montréal Distribution Centre',
        'code' => 'YUL',
    ]);
    $secondaryWarehouse = Warehouse::factory()->create([
        'name' => 'Toronto Distribution Centre',
        'code' => 'YYZ',
    ]);
    $healthyProduct = Product::factory()->create([
        'name' => 'Healthy Product',
        'sku' => 'SKU-HEALTHY',
    ]);
    $lowStockProduct = Product::factory()->create([
        'name' => 'Low Stock Product',
        'sku' => 'SKU-LOW',
    ]);

    InventoryBalance::factory()->create([
        'product_id' => $healthyProduct->id,
        'warehouse_id' => $primaryWarehouse->id,
        'quantity' => 20,
    ]);
    InventoryBalance::factory()->create([
        'product_id' => $healthyProduct->id,
        'warehouse_id' => $secondaryWarehouse->id,
        'quantity' => 10,
    ]);
    InventoryBalance::factory()->create([
        'product_id' => $lowStockProduct->id,
        'warehouse_id' => $primaryWarehouse->id,
        'quantity' => 8,
    ]);

    $order = Order::factory()->create([
        'number' => 'ORD-DASHBOARD',
        'customer_name' => 'Linvara Retail',
    ]);
    $healthyLine = OrderLine::factory()->create([
        'order_id' => $order->id,
        'product_id' => $healthyProduct->id,
        'quantity_ordered' => 12,
        'unit_price' => 10,
    ]);
    $lowStockLine = OrderLine::factory()->create([
        'order_id' => $order->id,
        'product_id' => $lowStockProduct->id,
        'quantity_ordered' => 5,
        'unit_price' => 20,
    ]);
    InventoryReservation::factory()->create([
        'order_line_id' => $healthyLine->id,
        'warehouse_id' => $primaryWarehouse->id,
        'quantity' => 4,
    ]);
    InventoryReservation::factory()->create([
        'order_line_id' => $lowStockLine->id,
        'warehouse_id' => $primaryWarehouse->id,
        'quantity' => 3,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->where('summary', [
            'physical_stock' => 38,
            'reserved_stock' => 7,
            'available_stock' => 31,
            'orders_count' => 1,
            'products_count' => 2,
            'warehouses_count' => 2,
        ])
        ->where('fulfillment', [
            'ordered_units' => 17,
            'reserved_units' => 7,
            'pending_units' => 10,
            'rate' => 41.2,
        ])
        ->has('warehouses', 2)
        ->where('warehouses.0', [
            'id' => $primaryWarehouse->id,
            'name' => 'Montréal Distribution Centre',
            'code' => 'YUL',
            'physical_stock' => 28,
            'reserved_stock' => 7,
            'available_stock' => 21,
            'reserved_percentage' => 25,
        ])
        ->has('recent_orders', 1)
        ->where('recent_orders.0.number', 'ORD-DASHBOARD')
        ->where('recent_orders.0.total', 220)
        ->where('recent_orders.0.fulfillment_rate', 41.2)
        ->has('inventory_alerts', 1)
        ->where('inventory_alerts.0', [
            'id' => $lowStockProduct->id,
            'name' => 'Low Stock Product',
            'sku' => 'SKU-LOW',
            'physical_stock' => 8,
            'reserved_stock' => 3,
            'available_stock' => 5,
            'severity' => 'low',
        ])
    );
});
