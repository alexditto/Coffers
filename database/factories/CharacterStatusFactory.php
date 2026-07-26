<?php

namespace Database\Factories;

use App\Models\CharacterStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CharacterStatus>
 */
class CharacterStatusFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['stunned', 'blinded', 'deafened', 'poisoned', 'Surprized', 'paralyzed']),
            'description' => $this->faker->sentence(),
            'effect' => $this->faker->sentence(),
        ];
    }
}
