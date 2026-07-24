<?php

namespace Database\Factories;

use App\Models\NPC;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NPC>
 */
class NPCFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'description' => $this->faker->paragraph(),
            'campaign_id' => null,
            'scene_id' => null,
            'owner_id' => null,
        ];
    }
}
