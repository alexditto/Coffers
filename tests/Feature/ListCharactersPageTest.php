<?php

use App\Models\Campaign;
use App\Models\Character;
use App\Models\CharacterSheet;
use App\Models\CharacterStatus;
use App\Models\Inventory;
use App\Models\User;
use Livewire\Livewire;

test('shows an empty state when no campaign is selected', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('list-characters-page')
        ->assertSee('Select or create a campaign');
});

test('shows an empty state for a campaign the user does not have access to', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('list-characters-page')
        ->assertSee('Select or create a campaign');
});

test('shows an empty state when the campaign has no characters', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('list-characters-page')
        ->assertSee('The Party · 0')
        ->assertSee('No characters in this campaign yet.');
});

test('lists only the characters belonging to the selected campaign', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $otherCampaign = Campaign::factory()->create(['owner_id' => $owner->id]);

    Character::factory()->create(['name' => 'Thornwick', 'campaign_id' => $campaign->id]);
    Character::factory()->create(['name' => 'Elowen', 'campaign_id' => $campaign->id]);
    Character::factory()->create(['name' => 'Stranger', 'campaign_id' => $otherCampaign->id]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('list-characters-page')
        ->assertSee('The Party · 2')
        ->assertSee('Thornwick')
        ->assertSee('Elowen')
        ->assertDontSee('Stranger');
});

test('shows class, level, health, and status from the character sheet', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $character = Character::factory()->create(['name' => 'Mirabel', 'campaign_id' => $campaign->id]);
    $sheet = CharacterSheet::factory()->create([
        'character_id' => $character->id,
        'class' => 'cleric',
        'level' => 5,
        'health' => 45,
        'total_health' => 90,
    ]);
    $sheet->statuses()->attach(CharacterStatus::factory()->create(['name' => 'Poisoned']));
    $character->update(['character_sheet_id' => $sheet->id]);

    session(['selected_campaign_id' => $campaign->id]);

    $html = Livewire::actingAs($owner)
        ->test('list-characters-page')
        ->assertSee('Mirabel')
        ->assertSee('Cleric')
        ->assertSee('Lvl 5')
        ->assertSee('POISONED')
        ->assertSee('45/90')
        ->html();

    expect($html)->toContain('width: 50%');
});

test('displays healthy for a character sheet with no active condition', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $character = Character::factory()->create(['name' => 'Elowen', 'campaign_id' => $campaign->id]);
    $sheet = CharacterSheet::factory()->create([
        'character_id' => $character->id,
        'health' => 40,
        'total_health' => 40,
    ]);
    $character->update(['character_sheet_id' => $sheet->id]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('list-characters-page')
        ->assertSee('HEALTHY');
});

test('renders gracefully when a character has no character sheet yet', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    Character::factory()->create(['name' => 'New Recruit', 'campaign_id' => $campaign->id, 'character_sheet_id' => null]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('list-characters-page')
        ->assertSee('New Recruit')
        ->assertSee('Unknown class')
        ->assertSee('Lvl 1')
        ->assertSee('HEALTHY');
});

test('refreshes the party list when the campaign-switched event fires', function () {
    $owner = User::factory()->create();
    $firstCampaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $secondCampaign = Campaign::factory()->create(['owner_id' => $owner->id]);

    Character::factory()->create(['name' => 'Thornwick', 'campaign_id' => $firstCampaign->id]);
    Character::factory()->create(['name' => 'Grimm', 'campaign_id' => $secondCampaign->id]);

    session(['selected_campaign_id' => $firstCampaign->id]);

    $component = Livewire::actingAs($owner)
        ->test('list-characters-page')
        ->assertSee('Thornwick')
        ->assertDontSee('Grimm');

    // In production, campaign-selector-banner updates the session before dispatching
    // this event; simulate that here since we're dispatching it directly in the test.
    session(['selected_campaign_id' => $secondCampaign->id]);

    $component->dispatch('campaign-switched', campaignId: $secondCampaign->id)
        ->assertSee('Grimm')
        ->assertDontSee('Thornwick');
});

test('the owner can open the edit modal and it is prefilled with the character\'s current details', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $character = Character::factory()->create(['name' => 'Mirabel', 'campaign_id' => $campaign->id]);
    $sheet = CharacterSheet::factory()->create([
        'character_id' => $character->id,
        'health' => 45,
        'total_health' => 90,
    ]);
    $poisoned = CharacterStatus::factory()->create(['name' => 'Poisoned']);
    $sheet->statuses()->attach($poisoned);
    $character->update(['character_sheet_id' => $sheet->id]);
    $inventory = Inventory::factory()->create(['character_id' => $character->id, 'gold' => 120]);
    $character->update(['inventory_id' => $inventory->id]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('list-characters-page')
        ->call('editCharacter', $character->id)
        ->assertSet('editHealth', 45)
        ->assertSet('editConditionIds', [$poisoned->id])
        ->assertSet('editGold', 120)
        ->assertSee('Poisoned');
});

test('a non owner does not see the clickable affordance or edit modal', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $member->campaigns()->attach($campaign->id);
    Character::factory()->create(['name' => 'Mirabel', 'campaign_id' => $campaign->id]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($member)
        ->test('list-characters-page')
        ->assertDontSeeHtml('wire:click="editCharacter')
        ->assertDontSeeHtml('name="edit-character"');
});

test('the owner can update health, conditions, and gold for an existing sheet and inventory', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $character = Character::factory()->create(['campaign_id' => $campaign->id]);
    $sheet = CharacterSheet::factory()->create([
        'character_id' => $character->id,
        'health' => 10,
        'total_health' => 50,
    ]);
    $character->update(['character_sheet_id' => $sheet->id]);
    $inventory = Inventory::factory()->create(['character_id' => $character->id, 'gold' => 5]);
    $character->update(['inventory_id' => $inventory->id]);
    $stunned = CharacterStatus::factory()->create(['name' => 'Stunned']);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('list-characters-page')
        ->call('editCharacter', $character->id)
        ->set('editHealth', 35)
        ->set('editConditionIds', [$stunned->id])
        ->set('editGold', 250)
        ->call('saveCharacter')
        ->assertHasNoErrors();

    expect($sheet->fresh()->health)->toBe(35)
        ->and($sheet->fresh()->statuses->pluck('id')->all())->toBe([$stunned->id]);

    expect($inventory->fresh()->gold)->toBe(250);
});

test('the owner can remove a condition from a character', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $character = Character::factory()->create(['campaign_id' => $campaign->id]);
    $sheet = CharacterSheet::factory()->create(['character_id' => $character->id, 'health' => 10, 'total_health' => 50]);
    $sheet->statuses()->attach(CharacterStatus::factory()->create(['name' => 'Stunned']));
    $character->update(['character_sheet_id' => $sheet->id]);
    $inventory = Inventory::factory()->create(['character_id' => $character->id, 'gold' => 5]);
    $character->update(['inventory_id' => $inventory->id]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('list-characters-page')
        ->call('editCharacter', $character->id)
        ->set('editConditionIds', [])
        ->set('editGold', 5)
        ->call('saveCharacter')
        ->assertHasNoErrors();

    expect($sheet->fresh()->statuses)->toBeEmpty();
});

test('health cannot exceed the character\'s max health', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $character = Character::factory()->create(['campaign_id' => $campaign->id]);
    $sheet = CharacterSheet::factory()->create([
        'character_id' => $character->id,
        'health' => 10,
        'total_health' => 50,
    ]);
    $character->update(['character_sheet_id' => $sheet->id]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('list-characters-page')
        ->call('editCharacter', $character->id)
        ->set('editHealth', 999)
        ->call('saveCharacter')
        ->assertHasErrors(['editHealth']);

    expect($sheet->fresh()->health)->toBe(10);
});

test('saving creates a character sheet and inventory when the character has none', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $character = Character::factory()->create([
        'campaign_id' => $campaign->id,
        'character_sheet_id' => null,
        'inventory_id' => null,
    ]);
    $poisoned = CharacterStatus::factory()->create(['name' => 'Poisoned']);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('list-characters-page')
        ->call('editCharacter', $character->id)
        ->set('editHealth', 80)
        ->set('editConditionIds', [$poisoned->id])
        ->set('editGold', 40)
        ->call('saveCharacter')
        ->assertHasNoErrors();

    $character->refresh();

    expect($character->character_sheet)->not->toBeNull()
        ->and($character->character_sheet->health)->toBe(80)
        ->and($character->character_sheet->statuses->pluck('id')->all())->toBe([$poisoned->id])
        ->and($character->inventory)->not->toBeNull()
        ->and($character->inventory->gold)->toBe(40);
});

test('a non owner cannot edit or save character details', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $member->campaigns()->attach($campaign->id);
    $character = Character::factory()->create(['campaign_id' => $campaign->id]);
    $sheet = CharacterSheet::factory()->create(['character_id' => $character->id, 'health' => 10, 'total_health' => 50]);
    $character->update(['character_sheet_id' => $sheet->id]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($member)->test('list-characters-page')->call('editCharacter', $character->id)->assertForbidden();

    Livewire::actingAs($member)
        ->test('list-characters-page')
        ->set('editingCharacterId', $character->id)
        ->set('editHealth', 999)
        ->call('saveCharacter')
        ->assertForbidden();

    expect($sheet->fresh()->health)->toBe(10);
});

test('cannot edit a character belonging to a different campaign by guessing its id', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $otherCharacter = Character::factory()->create();
    $sheet = CharacterSheet::factory()->create(['character_id' => $otherCharacter->id, 'health' => 10, 'total_health' => 50]);
    $otherCharacter->update(['character_sheet_id' => $sheet->id]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('list-characters-page')
        ->set('editingCharacterId', $otherCharacter->id)
        ->set('editHealth', 999)
        ->call('saveCharacter');

    expect($sheet->fresh()->health)->toBe(10);
});
