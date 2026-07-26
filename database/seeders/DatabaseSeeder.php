<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\Character;
use App\Models\CharacterSheet;
use App\Models\CharacterStatus;
use App\Models\Inventory;
use App\Models\ItemCount;
use App\Models\Journal;
use App\Models\Scene;
use App\Models\Shop;
use App\Models\ShopStock;
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
            'password' => bcrypt('sCQxTwWHYhFwfK9'),
            'email_verified_at' => now(),
        ]);
        CharacterStatus::factory()->create([
            'name' => 'Dead',
            'description' => 'The character is dead.',
            'effect' => 'The character is dead.'
        ]);
        CharacterStatus::factory()->create([
            'name' => 'Unconscious',
            'description' => 'The character is unconscious.',
            'effect' => 'The character is unconscious.'
        ]);
        CharacterStatus::factory()->create([
            'name' => 'Blinded',
            'description' => 'The character is blinded.',
            'effect' => 'The character is blinded.'
        ]);
        CharacterStatus::factory()->create([
            'name' => 'Deafened',
            'description' => 'The character is deafened.',
            'effect' => 'The character is deafened.'
        ]);
        CharacterStatus::factory()->create([
            'name' => 'Paralyzed',
            'description' => 'The character is paralyzed.',
            'effect' => 'The character is paralyzed.'
        ]);
        CharacterStatus::factory()->create([
            'name' => 'Stunned',
            'description' => 'The character is stunned.',
            'effect' => 'The character is stunned.'
        ]);
        CharacterStatus::factory()->create([
            'name' => 'Poisoned',
            'description' => 'The character is poisoned.',
            'effect' => 'The character is poisoned.'
        ]);

        User::factory()
            ->hasAttached(Campaign::factory(2)->create()->each(function ($campaign) {
                Character::factory()->count(4)->create(['campaign_id' => $campaign->id, 'character_sheet_id' => null, 'inventory_id' => null])->each(function ($character) {
                    $characterSheet = CharacterSheet::factory()->create(['character_id' => $character->id]);
                    $randomStatus = CharacterStatus::inRandomOrder()->first();
                    $characterSheet->statuses()->attach($randomStatus->id);
                    $character->update(['character_sheet_id' => $characterSheet->id]);
                    $inventory = Inventory::factory()->create(['character_id' => $character->id]);
                    $inventory->item_counts()->createMany(ItemCount::factory(5)->make([
                        'inventory_id' => $inventory->id,
                    ])->toArray());
                    $character->update(['inventory_id' => $inventory->id]);
                });
                Journal::factory()->create(['campaign_id' => $campaign->id]);
            }))
            ->create([
                'name' => 'Test User',
                'email' => 'alexdittocareer@gmail.com',
                'role' => 'user',
                'password' => bcrypt('sCQxTwWHYhFwfK9'),
                'email_verified_at' => now(),
            ])
            ->each(function ($user) {
                Shop::factory(3)->create(['owner_id' => $user->id])->each(function ($shop) use ($user) {
                    $shop->stock()->createMany(ShopStock::factory(5)->make([
                        'shop_id' => $shop->id,
                    ])->toArray());
                    $shop->campaigns()->attach(Campaign::factory(2)->create(['owner_id' => $user->id])->each(function ($campaign) {
                        Scene::factory(3)->create(['campaign_id' => $campaign->id]);
                    })->pluck('id')->toArray());
                });
            });

    }
}
