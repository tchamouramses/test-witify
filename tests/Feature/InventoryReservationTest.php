<?php

use App\Models\InventoryBalance;
use App\Models\InventoryMovement;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Testing\AssertableInertia as Assert;

test('users can reserve available inventory for an order line', function () {
    $this->actingAs(User::factory()->create());

    $order = Order::factory()->create();
    $line = OrderLine::factory()->create([
        'order_id' => $order->id,
        'quantity_ordered' => 8,
    ]);
    $warehouse = Warehouse::factory()->create();
    $balance = InventoryBalance::factory()->create([
        'product_id' => $line->product_id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 10,
    ]);

    $response = $this->post(route('orders.lines.reservations.store', [$order, $line]), [
        'warehouse_id' => $warehouse->id,
        'quantity' => 5,
    ]);

    $response->assertRedirect()->assertSessionHasNoErrors();

    $reservation = InventoryReservation::sole();

    expect($reservation->order_line_id)->toBe($line->id)
        ->and($reservation->warehouse_id)->toBe($warehouse->id)
        ->and($reservation->quantity)->toBe(5)
        ->and($balance->refresh()->quantity)->toBe(10)
        ->and(InventoryMovement::count())->toBe(0);
});

test('a reservation cannot exceed inventory available after existing reservations', function () {
    $this->actingAs(User::factory()->create());

    $product = Product::factory()->create();
    $warehouse = Warehouse::factory()->create();
    $order = Order::factory()->create();
    $line = OrderLine::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity_ordered' => 10,
    ]);
    $otherLine = OrderLine::factory()->create([
        'product_id' => $product->id,
        'quantity_ordered' => 10,
    ]);
    InventoryBalance::factory()->create([
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 6,
    ]);
    InventoryReservation::factory()->create([
        'order_line_id' => $otherLine->id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 4,
    ]);
    $initialTransactionLevel = DB::transactionLevel();
    Log::spy();

    $response = $this->from(route('orders.show', $order))
        ->post(route('orders.lines.reservations.store', [$order, $line]), [
            'warehouse_id' => $warehouse->id,
            'quantity' => 3,
        ]);

    $response->assertRedirect(route('orders.show', $order))
        ->assertSessionHasErrors([
            'quantity' => 'Only 2 units are available in this warehouse.',
        ]);

    expect(InventoryReservation::count())->toBe(1)
        ->and(DB::transactionLevel())->toBe($initialTransactionLevel);

    Log::shouldHaveReceived('warning')
        ->once()
        ->withArgs(fn (string $message, array $context): bool => $message === 'Inventory reservation rejected.'
            && $context['error_code'] === 'warehouse_availability_exceeded'
            && $context['order_line_id'] === $line->id
            && $context['warehouse_id'] === $warehouse->id
            && $context['requested_quantity'] === 3
            && $context['available_quantity'] === 2);
});

test('a reservation cannot exceed the quantity remaining on the order line', function () {
    $this->actingAs(User::factory()->create());

    $order = Order::factory()->create();
    $line = OrderLine::factory()->create([
        'order_id' => $order->id,
        'quantity_ordered' => 5,
    ]);
    $warehouse = Warehouse::factory()->create();
    InventoryBalance::factory()->create([
        'product_id' => $line->product_id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 20,
    ]);
    $reservation = InventoryReservation::factory()->create([
        'order_line_id' => $line->id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 3,
    ]);

    $response = $this->post(route('orders.lines.reservations.store', [$order, $line]), [
        'warehouse_id' => $warehouse->id,
        'quantity' => 3,
    ]);

    $response->assertSessionHasErrors([
        'quantity' => 'Only 2 units remain to be reserved for this order line.',
    ]);

    expect($reservation->refresh()->quantity)->toBe(3);
});

test('repeated reservations for the same line and warehouse are aggregated', function () {
    $this->actingAs(User::factory()->create());

    $order = Order::factory()->create();
    $line = OrderLine::factory()->create([
        'order_id' => $order->id,
        'quantity_ordered' => 10,
    ]);
    $warehouse = Warehouse::factory()->create();
    InventoryBalance::factory()->create([
        'product_id' => $line->product_id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 10,
    ]);

    foreach ([2, 3] as $quantity) {
        $this->post(route('orders.lines.reservations.store', [$order, $line]), [
            'warehouse_id' => $warehouse->id,
            'quantity' => $quantity,
        ])->assertSessionHasNoErrors();
    }

    expect(InventoryReservation::count())->toBe(1)
        ->and(InventoryReservation::sole()->quantity)->toBe(5);
});

test('reservation validation returns field specific custom messages', function (array $data, string $field, string $message) {
    $this->actingAs(User::factory()->create());

    $order = Order::factory()->create();
    $line = OrderLine::factory()->create(['order_id' => $order->id]);

    $this->post(route('orders.lines.reservations.store', [$order, $line]), $data)
        ->assertSessionHasErrors([$field => $message]);
})->with([
    'warehouse required' => [
        ['quantity' => 1],
        'warehouse_id',
        'Select a warehouse.',
    ],
    'quantity must be positive' => [
        ['warehouse_id' => 1, 'quantity' => 0],
        'quantity',
        'The quantity must be greater than zero.',
    ],
]);

test('an order line cannot be reserved through another order', function () {
    $this->actingAs(User::factory()->create());

    $order = Order::factory()->create();
    $line = OrderLine::factory()->create();

    $this->post(route('orders.lines.reservations.store', [$order, $line]), [
        'warehouse_id' => Warehouse::factory()->create()->id,
        'quantity' => 1,
    ])->assertNotFound();
});

test('the order page returns updated reservation and warehouse availability', function () {
    $this->actingAs(User::factory()->create());

    $order = Order::factory()->create();
    $line = OrderLine::factory()->create([
        'order_id' => $order->id,
        'quantity_ordered' => 8,
    ]);
    $warehouse = Warehouse::factory()->create();
    InventoryBalance::factory()->create([
        'product_id' => $line->product_id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 10,
    ]);
    InventoryReservation::factory()->create([
        'order_line_id' => $line->id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 3,
    ]);

    $this->get(route('orders.show', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('order.lines.0.quantity_reserved', 3)
            ->where('order.lines.0.quantity_remaining', 5)
            ->where('order.lines.0.warehouses.0.id', $warehouse->id)
            ->where('order.lines.0.warehouses.0.available_quantity', 7)
        );
});
