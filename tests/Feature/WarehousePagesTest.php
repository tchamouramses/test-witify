<?php

use App\Models\InventoryBalance;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('warehouses.index'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can list warehouses with their stock summary', function () {
    $this->actingAs(User::factory()->create());

    $warehouse = Warehouse::factory()->create();
    [$stockedProduct, $emptiedProduct] = Product::factory()->count(2)->create();

    InventoryBalance::factory()->create([
        'product_id' => $stockedProduct->id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 42,
    ]);

    // This product was fully removed: it must not count as "in stock".
    InventoryBalance::factory()->empty()->create([
        'product_id' => $emptiedProduct->id,
        'warehouse_id' => $warehouse->id,
    ]);

    $response = $this->get(route('warehouses.index'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('warehouses/Index')
        ->has('warehouses.data', 1)
        ->where('warehouses.per_page', 12)
        ->where('warehouses.data.0.name', $warehouse->name)
        ->where('warehouses.data.0.code', $warehouse->code)
        ->where('warehouses.data.0.products_in_stock', 1)
        ->where('warehouses.data.0.units_in_stock', 42)
    );
});

test('a warehouse with no balances shows empty stock', function () {
    $this->actingAs(User::factory()->create());

    Warehouse::factory()->create();

    $response = $this->get(route('warehouses.index'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('warehouses/Index')
        ->has('warehouses.data', 1)
        ->where('warehouses.data.0.products_in_stock', 0)
        ->where('warehouses.data.0.units_in_stock', 0)
    );
});

test('warehouses are paginated twelve at a time', function () {
    $this->actingAs(User::factory()->create());

    Warehouse::factory()->count(13)->create();

    $response = $this->get(route('warehouses.index', ['page' => 2]));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('warehouses/Index')
        ->has('warehouses.data', 1)
        ->where('warehouses.current_page', 2)
        ->where('warehouses.per_page', 12)
        ->where('warehouses.total', 13)
        ->where('warehouses.last_page', 2)
    );
});
