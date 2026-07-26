<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Campaign>
 */
class CampaignFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Campaign 1', 'Campaign 2', 'Campaign 3']),
            'description' => fake()->text(),
            'status' => fake()->randomElement(['active', 'inactive']),
            'owner_id' => User::factory(),
            'next_session_date' => now()->addDays(fake()->numberBetween(1, 30)),
        ];
    }
}
