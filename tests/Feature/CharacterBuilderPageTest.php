<?php

use App\Models\Campaign;
use App\Models\Character;
use App\Models\CharacterSheet;
use App\Models\Friend;
use App\Models\Inventory;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected to the login page', function () {
    $this->get(route('character-builder'))->assertRedirect(route('login'));
});

test('authenticated users can visit the character builder page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('character-builder'))->assertOk();
});

test('shows an empty state when the user has no characters', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('character-builder-page')
        ->assertSee('created any characters yet');
});

test('shows the users characters with their campaign attachment status', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create(['name' => 'Crimson Vale']);
    Character::factory()->create(['user_id' => $user->id, 'name' => 'Thornwick', 'campaign_id' => $campaign->id]);
    Character::factory()->create(['user_id' => $user->id, 'name' => 'Elowen', 'campaign_id' => null]);

    Livewire::actingAs($user)
        ->test('character-builder-page')
        ->assertSee('Thornwick')
        ->assertSee('In Crimson Vale')
        ->assertSee('Elowen')
        ->assertSee('Unattached');
});

test('only shows the authenticated users own characters', function () {
    $user = User::factory()->create();
    $stranger = User::factory()->create();
    Character::factory()->create(['user_id' => $stranger->id, 'name' => "Stranger's Character"]);

    Livewire::actingAs($user)
        ->test('character-builder-page')
        ->assertDontSee('Stranger');
});

test('the user can create a new character with a character sheet', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('character-builder-page')
        ->call('newCharacter')
        ->assertSet('characterName', '')
        ->set('characterName', 'Thornwick')
        ->set('characterClass', 'rogue')
        ->set('characterRace', 'elf')
        ->set('characterLevel', 5)
        ->set('characterAlignment', 'chaotic good')
        ->set('characterTotalHealth', 42)
        ->set('characterAc', 16)
        ->call('save')
        ->assertHasNoErrors();

    $character = Character::where('user_id', $user->id)->where('name', 'Thornwick')->firstOrFail();

    expect($character->campaign_id)->toBeNull()
        ->and($character->character_sheet)->not->toBeNull()
        ->and($character->character_sheet->class)->toBe('rogue')
        ->and($character->character_sheet->race)->toBe('elf')
        ->and($character->character_sheet->level)->toBe(5)
        ->and($character->character_sheet->alignment)->toBe('chaotic good')
        ->and($character->character_sheet->total_health)->toBe(42)
        ->and($character->character_sheet->health)->toBe(42)
        ->and($character->character_sheet->ac)->toBe(16);
});

test('editing prefills the form from the character and its sheet', function () {
    $user = User::factory()->create();
    $character = Character::factory()->create(['user_id' => $user->id, 'name' => 'Thornwick']);
    $sheet = CharacterSheet::factory()->create([
        'character_id' => $character->id,
        'class' => 'rogue',
        'race' => 'elf',
        'level' => 5,
    ]);
    $character->update(['character_sheet_id' => $sheet->id]);

    Livewire::actingAs($user)
        ->test('character-builder-page')
        ->call('edit', $character->id)
        ->assertSet('characterName', 'Thornwick')
        ->assertSet('characterClass', 'rogue')
        ->assertSet('characterRace', 'elf')
        ->assertSet('characterLevel', 5);
});

test('the user can update an existing character and its sheet', function () {
    $user = User::factory()->create();
    $character = Character::factory()->create(['user_id' => $user->id, 'name' => 'Old Name']);
    $sheet = CharacterSheet::factory()->create(['character_id' => $character->id, 'health' => 38, 'total_health' => 42]);
    $character->update(['character_sheet_id' => $sheet->id]);

    Livewire::actingAs($user)
        ->test('character-builder-page')
        ->call('edit', $character->id)
        ->set('characterName', 'New Name')
        ->set('characterClass', 'wizard')
        ->call('save')
        ->assertHasNoErrors();

    expect($character->fresh()->name)->toBe('New Name');
    expect($sheet->fresh()->class)->toBe('wizard');
});

test('lowering max health clamps current health down to the new max', function () {
    $user = User::factory()->create();
    $character = Character::factory()->create(['user_id' => $user->id]);
    $sheet = CharacterSheet::factory()->create(['character_id' => $character->id, 'health' => 38, 'total_health' => 42]);
    $character->update(['character_sheet_id' => $sheet->id]);

    Livewire::actingAs($user)
        ->test('character-builder-page')
        ->call('edit', $character->id)
        ->set('characterTotalHealth', 20)
        ->call('save');

    expect($sheet->fresh())->total_health->toBe(20)->health->toBe(20);
});

test('editing a character with no sheet creates one on save', function () {
    $user = User::factory()->create();
    $character = Character::factory()->create(['user_id' => $user->id, 'character_sheet_id' => null]);

    Livewire::actingAs($user)
        ->test('character-builder-page')
        ->call('edit', $character->id)
        ->set('characterClass', 'cleric')
        ->set('characterTotalHealth', 30)
        ->call('save')
        ->assertHasNoErrors();

    $character->refresh();

    expect($character->character_sheet)->not->toBeNull()
        ->and($character->character_sheet->class)->toBe('cleric')
        ->and($character->character_sheet->health)->toBe(30);
});

test('rejects an invalid alignment', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('character-builder-page')
        ->call('newCharacter')
        ->set('characterName', 'Thornwick')
        ->set('characterAlignment', 'evil overlord')
        ->call('save')
        ->assertHasErrors(['characterAlignment']);

    expect(Character::where('name', 'Thornwick')->exists())->toBeFalse();
});

test('cannot edit another users character by guessing its id', function () {
    $user = User::factory()->create();
    $stranger = User::factory()->create();
    $strangerCharacter = Character::factory()->create(['user_id' => $stranger->id, 'name' => 'Original Name']);

    Livewire::actingAs($user)
        ->test('character-builder-page')
        ->call('edit', $strangerCharacter->id)
        ->set('characterName', 'Hacked Name')
        ->call('save');

    expect($strangerCharacter->fresh()->name)->toBe('Original Name');
});

test('the user can duplicate a character including its sheet, starting fresh', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $character = Character::factory()->create(['user_id' => $user->id, 'name' => 'Thornwick', 'campaign_id' => $campaign->id]);
    $sheet = CharacterSheet::factory()->create([
        'character_id' => $character->id,
        'class' => 'rogue',
        'race' => 'elf',
        'health' => 10,
        'total_health' => 42,
        'status' => 'poisoned',
    ]);
    $character->update(['character_sheet_id' => $sheet->id]);
    $inventory = Inventory::factory()->create(['character_id' => $character->id, 'gold' => 500]);
    $character->update(['inventory_id' => $inventory->id]);

    Livewire::actingAs($user)
        ->test('character-builder-page')
        ->call('duplicate', $character->id)
        ->assertHasNoErrors();

    $copy = Character::where('name', 'Thornwick (Copy)')->firstOrFail();

    expect($copy->campaign_id)->toBeNull()
        ->and($copy->inventory_id)->toBeNull()
        ->and($copy->character_sheet)->not->toBeNull()
        ->and($copy->character_sheet->class)->toBe('rogue')
        ->and($copy->character_sheet->race)->toBe('elf')
        ->and($copy->character_sheet->total_health)->toBe(42)
        ->and($copy->character_sheet->health)->toBe(42)
        ->and($copy->character_sheet->status)->toBe('none');
});

test('duplicating a character with no sheet does not error and creates no sheet', function () {
    $user = User::factory()->create();
    $character = Character::factory()->create(['user_id' => $user->id, 'name' => 'Bare Bones', 'character_sheet_id' => null]);

    Livewire::actingAs($user)
        ->test('character-builder-page')
        ->call('duplicate', $character->id);

    $copy = Character::where('name', 'Bare Bones (Copy)')->firstOrFail();

    expect($copy->character_sheet)->toBeNull();
});

test('cannot duplicate another users character by guessing its id', function () {
    $user = User::factory()->create();
    $stranger = User::factory()->create();
    $strangerCharacter = Character::factory()->create(['user_id' => $stranger->id, 'name' => 'Original']);

    Livewire::actingAs($user)
        ->test('character-builder-page')
        ->call('duplicate', $strangerCharacter->id);

    expect(Character::where('name', 'Original (Copy)')->exists())->toBeFalse();
});

test('confirming delete shows the character name and campaign warning', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create(['name' => 'Crimson Vale']);
    $character = Character::factory()->create(['user_id' => $user->id, 'name' => 'Thornwick', 'campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('character-builder-page')
        ->call('edit', $character->id)
        ->call('confirmDelete')
        ->assertSee('Thornwick')
        ->assertSee('Crimson Vale');
});

test('the user can delete a character along with its sheet and inventory', function () {
    $user = User::factory()->create();
    $character = Character::factory()->create(['user_id' => $user->id]);
    $sheet = CharacterSheet::factory()->create(['character_id' => $character->id]);
    $character->update(['character_sheet_id' => $sheet->id]);
    $inventory = Inventory::factory()->create(['character_id' => $character->id]);
    $character->update(['inventory_id' => $inventory->id]);

    Livewire::actingAs($user)
        ->test('character-builder-page')
        ->call('edit', $character->id)
        ->call('confirmDelete')
        ->call('deleteCharacter');

    expect(Character::find($character->id))->toBeNull()
        ->and(CharacterSheet::find($sheet->id))->toBeNull()
        ->and(Inventory::find($inventory->id))->toBeNull();
});

test('cannot delete another users character by guessing its id', function () {
    $user = User::factory()->create();
    $stranger = User::factory()->create();
    $strangerCharacter = Character::factory()->create(['user_id' => $stranger->id]);

    Livewire::actingAs($user)
        ->test('character-builder-page')
        ->set('deletingCharacterId', $strangerCharacter->id)
        ->call('deleteCharacter');

    expect(Character::find($strangerCharacter->id))->not->toBeNull();
});

test('only shows campaigns owned by approved friends when joining', function () {
    $user = User::factory()->create();
    $character = Character::factory()->create(['user_id' => $user->id, 'campaign_id' => null]);

    $friend = User::factory()->create();
    Friend::factory()->create(['user_id' => $user->id, 'friend_id' => $friend->id, 'status' => 'approved']);
    $friendCampaign = Campaign::factory()->create(['owner_id' => $friend->id, 'name' => 'Friend Campaign']);

    $stranger = User::factory()->create();
    Campaign::factory()->create(['owner_id' => $stranger->id, 'name' => 'Stranger Campaign']);

    $pendingFriend = User::factory()->create();
    Friend::factory()->create(['user_id' => $user->id, 'friend_id' => $pendingFriend->id, 'status' => 'pending']);
    Campaign::factory()->create(['owner_id' => $pendingFriend->id, 'name' => 'Pending Friend Campaign']);

    $component = Livewire::actingAs($user)
        ->test('character-builder-page')
        ->call('joinCampaign', $character->id);

    expect($component->get('friendCampaigns')->pluck('id')->all())->toBe([$friendCampaign->id]);

    $component->assertSee('Friend Campaign')
        ->assertDontSee('Stranger Campaign')
        ->assertDontSee('Pending Friend Campaign');
});

test('the user can join a friends campaign with an unattached character', function () {
    $user = User::factory()->create();
    $character = Character::factory()->create(['user_id' => $user->id, 'campaign_id' => null]);

    $friend = User::factory()->create();
    Friend::factory()->create(['user_id' => $user->id, 'friend_id' => $friend->id, 'status' => 'approved']);
    $campaign = Campaign::factory()->create(['owner_id' => $friend->id]);

    Livewire::actingAs($user)
        ->test('character-builder-page')
        ->call('joinCampaign', $character->id)
        ->set('joinCampaignId', $campaign->id)
        ->call('confirmJoinCampaign')
        ->assertHasNoErrors();

    expect($character->fresh()->campaign_id)->toBe($campaign->id)
        ->and($user->campaigns()->where('campaign_id', $campaign->id)->exists())->toBeTrue();
});

test('cannot join a campaign that does not belong to a friend', function () {
    $user = User::factory()->create();
    $character = Character::factory()->create(['user_id' => $user->id, 'campaign_id' => null]);

    $stranger = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $stranger->id]);

    Livewire::actingAs($user)
        ->test('character-builder-page')
        ->call('joinCampaign', $character->id)
        ->set('joinCampaignId', $campaign->id)
        ->call('confirmJoinCampaign')
        ->assertHasErrors(['joinCampaignId']);

    expect($character->fresh()->campaign_id)->toBeNull();
});

test('cannot join a campaign with another users character by guessing its id', function () {
    $user = User::factory()->create();
    $stranger = User::factory()->create();
    $strangerCharacter = Character::factory()->create(['user_id' => $stranger->id, 'campaign_id' => null]);

    $friend = User::factory()->create();
    Friend::factory()->create(['user_id' => $user->id, 'friend_id' => $friend->id, 'status' => 'approved']);
    $campaign = Campaign::factory()->create(['owner_id' => $friend->id]);

    Livewire::actingAs($user)
        ->test('character-builder-page')
        ->call('joinCampaign', $strangerCharacter->id)
        ->set('joinCampaignId', $campaign->id)
        ->call('confirmJoinCampaign');

    expect($strangerCharacter->fresh()->campaign_id)->toBeNull();
});

test('cannot join a campaign with an already attached character', function () {
    $user = User::factory()->create();
    $originalCampaign = Campaign::factory()->create();
    $character = Character::factory()->create(['user_id' => $user->id, 'campaign_id' => $originalCampaign->id]);

    $friend = User::factory()->create();
    Friend::factory()->create(['user_id' => $user->id, 'friend_id' => $friend->id, 'status' => 'approved']);
    $campaign = Campaign::factory()->create(['owner_id' => $friend->id]);

    Livewire::actingAs($user)
        ->test('character-builder-page')
        ->call('joinCampaign', $character->id)
        ->set('joinCampaignId', $campaign->id)
        ->call('confirmJoinCampaign');

    expect($character->fresh()->campaign_id)->toBe($originalCampaign->id);
});
