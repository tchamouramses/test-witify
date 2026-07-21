<?php

namespace Database\Seeders;

use App\Models\InventoryBalance;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class DemoSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed warehouses, products, inventory movements and demo orders.
     */
    public function run(): void
    {
        $warehouses = Warehouse::factory()->count(5)->create();
        $products = Product::factory()->count(60)->create();

        $this->seedInventoryMovements($products, $warehouses);
        $this->seedOrders($products, $warehouses);
    }

    /**
     * The movement journal is the full history of the inventory, while the
     * balances store the current quantity per pair for fast reads. Give most
     * pairs a small history (receivings and removals) rather than a single
     * row, and keep each balance equal to the sum of its movements.
     *
     * @param  Collection<int, Product>  $products
     * @param  Collection<int, Warehouse>  $warehouses
     */
    private function seedInventoryMovements(Collection $products, Collection $warehouses): void
    {
        $now = now();
        $movementRows = collect();
        $balanceRows = collect();

        foreach ($products as $product) {
            foreach ($warehouses as $warehouse) {
                if (fake()->boolean(15)) {
                    continue; // Out of stock: no movement history for this pair.
                }

                $stock = fake()->numberBetween(20, 200);

                $movementRows->push([
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'user_id' => null,
                    'quantity_change' => $stock,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $extraMovements = fake()->numberBetween(0, 3);

                for ($i = 0; $i < $extraMovements; $i++) {
                    $change = fake()->boolean()
                        ? fake()->numberBetween(1, 50)
                        : -fake()->numberBetween(1, min(20, $stock));

                    $stock += $change;

                    $movementRows->push([
                        'product_id' => $product->id,
                        'warehouse_id' => $warehouse->id,
                        'user_id' => null,
                        'quantity_change' => $change,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                $balanceRows->push([
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'quantity' => $stock,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $movementRows->chunk(500)->each(function (Collection $chunk): void {
            InventoryMovement::insert($chunk->values()->all());
        });

        $balanceRows->chunk(500)->each(function (Collection $chunk): void {
            InventoryBalance::insert($chunk->values()->all());
        });
    }

    /**
     * Create a handful of small orders plus one large 40-line order.
     *
     * @param  Collection<int, Product>  $products
     * @param  Collection<int, Warehouse>  $warehouses
     */
    private function seedOrders(Collection $products, Collection $warehouses): void
    {
        Order::factory()
            ->count(5)
            ->create()
            ->each(function (Order $order) use ($products): void {
                OrderLine::factory()
                    ->count(fake()->numberBetween(3, 6))
                    ->sequence(fn (): array => ['product_id' => $products->random()->id])
                    ->create(['order_id' => $order->id]);
            });

        $bigOrder = Order::factory()->create([
            'number' => 'ORD-100000',
            'customer_name' => 'Massive Dynamic Inc.',
        ]);

        /** @var Collection<int, Product> $pool */
        $pool = $products->random(30)->values();

        OrderLine::factory()
            ->count(37)
            ->sequence(fn (): array => ['product_id' => $pool->random()->id])
            ->create(['order_id' => $bigOrder->id]);

        $this->seedScarceLines($bigOrder, $pool, $warehouses);
    }

    /**
     * Guarantee a few lines whose ordered quantity exceeds the total stock on
     * hand, so "insufficient inventory" scenarios are reachable from the demo
     * data.
     *
     * @param  Collection<int, Product>  $pool
     * @param  Collection<int, Warehouse>  $warehouses
     */
    private function seedScarceLines(Order $order, Collection $pool, Collection $warehouses): void
    {
        $pool->take(3)->each(function (Product $product) use ($order, $warehouses): void {
            InventoryMovement::query()->where('product_id', $product->id)->delete();
            InventoryBalance::query()->where('product_id', $product->id)->delete();

            $warehouses->each(function (Warehouse $warehouse) use ($product): void {
                $quantity = fake()->numberBetween(0, 3);

                if ($quantity > 0) {
                    InventoryMovement::create([
                        'product_id' => $product->id,
                        'warehouse_id' => $warehouse->id,
                        'quantity_change' => $quantity,
                    ]);

                    InventoryBalance::create([
                        'product_id' => $product->id,
                        'warehouse_id' => $warehouse->id,
                        'quantity' => $quantity,
                    ]);
                }
            });

            OrderLine::factory()->create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity_ordered' => fake()->numberBetween(30, 60),
            ]);
        });
    }
}
