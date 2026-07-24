<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\Character;
use App\Models\CharacterSheet;
use App\Models\Inventory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Character>
 */
class CharacterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->name(),
            'character_sheet_id' => CharacterSheet::factory(),
            'campaign_id' => Campaign::factory(),
            'inventory_id' => Inventory::factory(),
        ];
    }
}
