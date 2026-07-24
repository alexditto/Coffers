<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\Shop;
use App\Models\ShopStock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShopStock>
 */
class ShopStockFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'item_id' => Item::factory(),
            'price' => fake()->numberBetween(1, 1000),
            'quantity' => fake()->numberBetween(1, 100),
        ];
    }
}
