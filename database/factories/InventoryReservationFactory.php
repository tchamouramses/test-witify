<?php

namespace Database\Factories;

use App\Models\InventoryReservation;
use App\Models\OrderLine;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryReservation>
 */
class InventoryReservationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_line_id' => OrderLine::factory(),
            'warehouse_id' => Warehouse::factory(),
            'quantity' => fake()->numberBetween(1, 10),
        ];
    }
}
