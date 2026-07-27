<?php

namespace Database\Factories;

use App\Models\Character;
use App\Models\CharacterSheet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CharacterSheet>
 */
class CharacterSheetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $totalHealth = fake()->numberBetween(11, 100);

        return [
            'character_id' => Character::factory(),
            'description' => fake()->text(),
            'class' => fake()->randomElement(['fighter', 'mage', 'rogue', 'ranger', 'wizard', 'cleric']),
            'race' => fake()->randomElement(['human', 'elf', 'dwarf', 'orc', 'halfling', 'gnome', 'dragonborn']),
            'level' => fake()->numberBetween(1, 5),
            'alignment' => fake()->randomElement(['lawful good', 'neutral good', 'chaotic good', 'lawful neutral', 'true neutral', 'chaotic neutral', 'lawful evil', 'neutral evil', 'chaotic evil']),
            'background' => fake()->text(),
            'health' => $totalHealth - fake()->numberBetween(0, 10),
            'total_health' => $totalHealth,
            'ac' => fake()->numberBetween(10, 20),
        ];
    }
}
