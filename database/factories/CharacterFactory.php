<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\Character;
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
            // Nullable by default: CharacterSheetFactory and InventoryFactory each default their
            // own `character_id` to Character::factory(), so eagerly creating them here would
            // recurse forever. Attach a sheet/inventory explicitly when a test needs one.
            'character_sheet_id' => null,
            'campaign_id' => Campaign::factory(),
            'inventory_id' => null,
        ];
    }
}
