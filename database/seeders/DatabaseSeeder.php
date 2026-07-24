<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'aditto@buildingstars.com',
            'role' => 'admin',
            "password" => bcrypt("sCQxTwWHYhFwfK9"),
            "email_verified_at" => now(),
        ]);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'alexdittocareer@gmail.com',
            'role' => 'user',
            "password" => bcrypt("sCQxTwWHYhFwfK9"),
            "email_verified_at" => now(),
        ])->each(function($user){
            Campaign::factory(2)->create(['owner_id' => $user->id]);
        });

        User::factory(10)->hasAttached(Campaign::factory(2))->create()->each(function($user){
            Campaign::factory(2)->create(['owner_id' => $user->id]);
        });
    }
}
