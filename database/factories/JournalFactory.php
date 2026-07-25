<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\Journal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Journal>
 */
class JournalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'title' => fake()->sentence(),
            'type' => fake()->randomElement(['npc', 'quest', 'place', 'lore']),
            'content' => fake()->paragraph(),
            'revealed' => fake()->boolean(),
        ];
    }
}
