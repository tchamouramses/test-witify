<?php

use App\Models\Order;
use App\Models\OrderLine;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('orders.index'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can list orders', function () {
    $this->actingAs(User::factory()->create());

    $order = Order::factory()->create();
    OrderLine::factory()->count(2)->create([
        'order_id' => $order->id,
        'quantity_ordered' => 3,
        'unit_price' => 10,
    ]);

    $response = $this->get(route('orders.index'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('orders/Index')
        ->has('orders.data', 1)
        ->where('orders.per_page', 12)
        ->where('orders.data.0.number', $order->number)
        ->where('orders.data.0.lines_count', 2)
        ->where('orders.data.0.total', 60)
    );
});

test('orders are paginated twelve at a time', function () {
    $this->actingAs(User::factory()->create());

    Order::factory()->count(13)->create();

    $response = $this->get(route('orders.index', ['page' => 2]));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('orders/Index')
        ->has('orders.data', 1)
        ->where('orders.current_page', 2)
        ->where('orders.per_page', 12)
        ->where('orders.total', 13)
        ->where('orders.last_page', 2)
    );
});

test('authenticated users can view an order with its lines', function () {
    $this->actingAs(User::factory()->create());

    $order = Order::factory()->create();
    $line = OrderLine::factory()->create([
        'order_id' => $order->id,
        'quantity_ordered' => 5,
        'unit_price' => 19.99,
    ]);

    $response = $this->get(route('orders.show', $order));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('orders/Show')
        ->where('order.number', $order->number)
        ->has('order.lines', 1)
        ->where('order.lines.0.product.name', $line->product->name)
        ->where('order.lines.0.quantity_ordered', 5)
        ->where('order.lines.0.quantity_reserved', 0)
        ->where('order.lines.0.quantity_remaining', 5)
        ->where('order.lines.0.line_total', 99.95)
        ->where('order.total', 99.95)
    );
});

test('an order detail keeps every order line in the recap', function () {
    $this->actingAs(User::factory()->create());

    $order = Order::factory()->create();
    OrderLine::factory()->count(13)->create(['order_id' => $order->id]);

    $response = $this->get(route('orders.show', $order));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('orders/Show')
        ->has('order.lines', 13)
    );
});
