<?php

use App\Models\Campaign;
use App\Models\Character;
use App\Models\CharacterSheet;
use App\Models\CharacterStatus;
use App\Models\Friend;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected to the login page', function () {
    $friend = User::factory()->create();

    $this->get(route('friend-profile', ['friend' => $friend->id]))->assertRedirect(route('login'));
});

test('an approved friend can view the profile with active campaigns and characters', function () {
    $user = User::factory()->create();
    $friend = User::factory()->create(['name' => 'Elowen']);

    Friend::factory()->create(['user_id' => $user->id, 'friend_id' => $friend->id, 'status' => 'approved']);

    $activeCampaign = Campaign::factory()->create(['name' => 'The Sunken Keep', 'status' => 'active', 'owner_id' => $friend->id]);
    Campaign::factory()->create(['name' => 'Retired Saga', 'status' => 'inactive', 'owner_id' => $friend->id]);

    $character = Character::factory()->create(['name' => 'Mannasha', 'user_id' => $friend->id, 'campaign_id' => $activeCampaign->id]);
    $sheet = CharacterSheet::factory()->create(['character_id' => $character->id, 'class' => 'rogue', 'level' => 4]);
    $character->update(['character_sheet_id' => $sheet->id]);
    $sheet->statuses()->attach(CharacterStatus::factory()->create(['name' => 'Poisoned']));

    Livewire::actingAs($user)
        ->test('friend-profile-page', ['friendId' => $friend->id])
        ->assertSee('Elowen')
        ->assertSee('The Sunken Keep')
        ->assertDontSee('Retired Saga')
        ->assertSee('Dungeon Master')
        ->assertSee('Mannasha')
        ->assertSee('POISONED');
});

test('a pending friend request does not grant access to the profile', function () {
    $user = User::factory()->create();
    $friend = User::factory()->create();

    Friend::factory()->create(['user_id' => $user->id, 'friend_id' => $friend->id, 'status' => 'pending']);

    Livewire::actingAs($user)
        ->test('friend-profile-page', ['friendId' => $friend->id])
        ->assertSee("This profile isn't available.", false);
});

test('a stranger cannot view a profile via a crafted url', function () {
    $user = User::factory()->create();
    $stranger = User::factory()->create();

    Livewire::actingAs($user)
        ->test('friend-profile-page', ['friendId' => $stranger->id])
        ->assertSee("This profile isn't available.", false);
});
