<?php

use App\Models\InventoryBalance;
use App\Models\InventoryMovement;
use App\Models\InventoryReservation;
use App\Models\OrderLine;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('inventory.index'));
    $response->assertRedirect(route('login'));
});

test('authenticated users see available inventory after reservations per warehouse', function () {
    $this->actingAs(User::factory()->create());

    $movement = InventoryMovement::factory()->create(['quantity_change' => 50]);
    InventoryMovement::factory()->create([
        'product_id' => $movement->product_id,
        'warehouse_id' => $movement->warehouse_id,
        'quantity_change' => -8,
    ]);
    InventoryBalance::factory()->create([
        'product_id' => $movement->product_id,
        'warehouse_id' => $movement->warehouse_id,
        'quantity' => 99,
    ]);
    $line = OrderLine::factory()->create([
        'product_id' => $movement->product_id,
    ]);
    InventoryReservation::factory()->create([
        'order_line_id' => $line->id,
        'warehouse_id' => $movement->warehouse_id,
        'quantity' => 8,
    ]);

    $response = $this->get(route('inventory.index'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('inventory/Index')
        ->has('warehouses', 1)
        ->has('products.data', 1)
        ->where('products.per_page', 12)
        ->where("products.data.0.quantities.{$movement->warehouse_id}", 34)
    );
});

test('inventory products are paginated twelve at a time', function () {
    $this->actingAs(User::factory()->create());

    Product::factory()->count(13)->create();

    $response = $this->get(route('inventory.index', ['page' => 2]));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('inventory/Index')
        ->has('products.data', 1)
        ->where('products.current_page', 2)
        ->where('products.per_page', 12)
        ->where('products.total', 13)
        ->where('products.last_page', 2)
    );
});

test('users can add stock to a warehouse', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $product = Product::factory()->create();
    $warehouse = Warehouse::factory()->create();

    $response = $this->post(route('inventory.adjustments.store'), [
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'quantity_change' => 10,
    ]);

    $response->assertRedirect()->assertSessionHasNoErrors();

    $balance = InventoryBalance::sole();
    expect($balance->quantity)->toBe(10);

    $movement = InventoryMovement::sole();
    expect($movement->quantity_change)->toBe(10)
        ->and($movement->user_id)->toBe($user->id);
});

test('users can remove stock from a warehouse', function () {
    $this->actingAs(User::factory()->create());

    $movement = InventoryMovement::factory()->create(['quantity_change' => 10]);
    $balance = InventoryBalance::factory()->create([
        'product_id' => $movement->product_id,
        'warehouse_id' => $movement->warehouse_id,
        'quantity' => 10,
    ]);

    $response = $this->post(route('inventory.adjustments.store'), [
        'product_id' => $movement->product_id,
        'warehouse_id' => $movement->warehouse_id,
        'quantity_change' => -4,
    ]);

    $response->assertRedirect()->assertSessionHasNoErrors();

    expect($balance->refresh()->quantity)->toBe(6)
        ->and(InventoryMovement::count())->toBe(2);
});

test('calculated stock cannot be adjusted below zero even when the balance has drifted', function () {
    $this->actingAs(User::factory()->create());

    $movement = InventoryMovement::factory()->create(['quantity_change' => 3]);
    $balance = InventoryBalance::factory()->create([
        'product_id' => $movement->product_id,
        'warehouse_id' => $movement->warehouse_id,
        'quantity' => 99,
    ]);

    $response = $this->from(route('inventory.index'))->post(route('inventory.adjustments.store'), [
        'product_id' => $movement->product_id,
        'warehouse_id' => $movement->warehouse_id,
        'quantity_change' => -4,
    ]);

    $response->assertSessionHasErrors([
        'quantity_change' => 'Only 3 units are in stock in this warehouse.',
    ]);

    expect($balance->refresh()->quantity)->toBe(99)
        ->and(InventoryMovement::count())->toBe(1);
});

test('stock cannot be adjusted below the quantity already reserved', function () {
    $this->actingAs(User::factory()->create());

    $movement = InventoryMovement::factory()->create(['quantity_change' => 10]);
    $balance = InventoryBalance::factory()->create([
        'product_id' => $movement->product_id,
        'warehouse_id' => $movement->warehouse_id,
        'quantity' => 10,
    ]);
    $line = OrderLine::factory()->create(['product_id' => $movement->product_id]);
    InventoryReservation::factory()->create([
        'order_line_id' => $line->id,
        'warehouse_id' => $movement->warehouse_id,
        'quantity' => 8,
    ]);

    $response = $this->post(route('inventory.adjustments.store'), [
        'product_id' => $movement->product_id,
        'warehouse_id' => $movement->warehouse_id,
        'quantity_change' => -3,
    ]);

    $response->assertSessionHasErrors([
        'quantity_change' => 'This adjustment would reduce stock below the 8 units already reserved.',
    ]);

    expect($balance->refresh()->quantity)->toBe(10)
        ->and(InventoryMovement::count())->toBe(1);
});

test('an adjustment resynchronizes a drifted balance from the journal', function () {
    $this->actingAs(User::factory()->create());

    $movement = InventoryMovement::factory()->create(['quantity_change' => 20]);

    // A balance that no longer matches its journal (e.g. written by a bug).
    InventoryBalance::factory()->create([
        'product_id' => $movement->product_id,
        'warehouse_id' => $movement->warehouse_id,
        'quantity' => 99,
    ]);

    $this->post(route('inventory.adjustments.store'), [
        'product_id' => $movement->product_id,
        'warehouse_id' => $movement->warehouse_id,
        'quantity_change' => 5,
    ])->assertSessionHasNoErrors();

    expect(InventoryBalance::sole()->quantity)->toBe(25);
});

test('the balance always equals the sum of the movement journal', function () {
    $this->actingAs(User::factory()->create());

    $product = Product::factory()->create();
    $warehouse = Warehouse::factory()->create();

    foreach ([10, 25, -8] as $quantityChange) {
        $this->post(route('inventory.adjustments.store'), [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity_change' => $quantityChange,
        ])->assertSessionHasNoErrors();
    }

    $balance = InventoryBalance::sole();

    expect($balance->quantity)->toBe(27)
        ->and(InventoryMovement::currentStock($product->id, $warehouse->id))->toBe(27)
        ->and(InventoryMovement::count())->toBe(3);
});

test('the adjustment quantity must not be zero', function (int|string $quantityChange) {
    $this->actingAs(User::factory()->create());

    $balance = InventoryBalance::factory()->create(['quantity' => 10]);

    $response = $this->post(route('inventory.adjustments.store'), [
        'product_id' => $balance->product_id,
        'warehouse_id' => $balance->warehouse_id,
        'quantity_change' => $quantityChange,
    ]);

    $response->assertSessionHasErrors('quantity_change');
})->with([
    'zero' => 0,
    'not a number' => 'abc',
]);
