<?php

namespace Database\Factories;

use App\Models\InventoryBalance;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryBalance>
 */
class InventoryBalanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'warehouse_id' => Warehouse::factory(),
            'quantity' => fake()->numberBetween(0, 200),
        ];
    }

    public function empty(): static
    {
        return $this->state(fn (array $attributes): array => [
            'quantity' => 0,
        ]);
    }
}
