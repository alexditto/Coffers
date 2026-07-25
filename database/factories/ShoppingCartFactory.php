<?php

namespace Database\Factories;

use App\Models\Inventory;
use App\Models\ShoppingCart;
use App\Models\ShopStock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShoppingCart>
 */
class ShoppingCartFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shop_stock_id' => ShopStock::factory(),
            'inventory_id' => Inventory::factory(),
            'is_purchased' => false,
        ];
    }
}
