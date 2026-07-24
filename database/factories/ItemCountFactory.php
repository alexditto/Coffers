<?php

namespace Database\Factories;

use App\Models\ItemCount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ItemCount>
 */
class ItemCountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'inventory_id' => \App\Models\Inventory::factory(),
            'item_id' => \App\Models\Item::factory(),
            'count' => fake()->numberBetween(1, 100),
        ];
    }
}
