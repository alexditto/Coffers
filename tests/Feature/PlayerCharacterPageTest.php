<?php

use App\Models\Campaign;
use App\Models\Character;
use App\Models\CharacterSheet;
use App\Models\Inventory;
use App\Models\User;
use Livewire\Livewire;

test('shows an empty state when no campaign is selected', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('player-character-page')
        ->assertSee('Select or create a campaign');
});

test('shows an empty state for a campaign the user does not have access to', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('player-character-page')
        ->assertSee('Select or create a campaign');
});

test('shows an empty state when the user has no character in the selected campaign', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('player-character-page')
        ->assertSee('character in this campaign yet');
});

test('shows the character name, race, level, and stats', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);
    $character = Character::factory()->create(['user_id' => $user->id, 'campaign_id' => $campaign->id, 'name' => 'Thornwick']);
    $sheet = CharacterSheet::factory()->create([
        'character_id' => $character->id,
        'race' => 'elf',
        'class' => 'rogue',
        'level' => 5,
        'health' => 38,
        'total_health' => 42,
        'ac' => 16,
    ]);
    $sheet->statuses()->attach(CharacterStatus::factory()->create(['name' => 'Poisoned']));
    $character->update(['character_sheet_id' => $sheet->id]);
    $inventory = Inventory::factory()->create(['character_id' => $character->id, 'gold' => 240]);
    $character->update(['inventory_id' => $inventory->id]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('player-character-page')
        ->assertSee('Thornwick')
        ->assertSee('Elf')
        ->assertSee('Lvl 5')
        ->assertSee('38/42')
        ->assertSee('16')
        ->assertSee('240')
        ->assertSee('POISONED');
});

test('the my party section lists other characters but excludes the users own', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);

    $character = Character::factory()->create(['user_id' => $user->id, 'campaign_id' => $campaign->id, 'name' => 'Thornwick']);
    $sheet = CharacterSheet::factory()->create(['character_id' => $character->id]);
    $character->update(['character_sheet_id' => $sheet->id]);

    $ally = Character::factory()->create(['campaign_id' => $campaign->id, 'name' => 'Mirabel']);
    $allySheet = CharacterSheet::factory()->create(['character_id' => $ally->id, 'race' => 'human', 'class' => 'cleric']);
    $ally->update(['character_sheet_id' => $allySheet->id]);

    session(['selected_campaign_id' => $campaign->id]);

    $component = Livewire::actingAs($user)
        ->test('player-character-page')
        ->assertSee('Mirabel')
        ->assertSee('Human')
        ->assertSee('Cleric');

    expect($component->get('partyMembers')->pluck('name')->all())->toBe(['Mirabel']);
});

test('the my party section shows each members current health and condition', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);

    $character = Character::factory()->create(['user_id' => $user->id, 'campaign_id' => $campaign->id, 'name' => 'Thornwick']);
    $sheet = CharacterSheet::factory()->create(['character_id' => $character->id]);
    $character->update(['character_sheet_id' => $sheet->id]);

    $healthyAlly = Character::factory()->create(['campaign_id' => $campaign->id, 'name' => 'Mirabel']);
    CharacterSheet::factory()->create(['character_id' => $healthyAlly->id, 'health' => 18, 'total_health' => 24]);

    $poisonedAlly = Character::factory()->create(['campaign_id' => $campaign->id, 'name' => 'Garrick']);
    $poisonedSheet = CharacterSheet::factory()->create(['character_id' => $poisonedAlly->id, 'health' => 6, 'total_health' => 30]);
    $poisonedSheet->statuses()->attach(CharacterStatus::factory()->create(['name' => 'Poisoned']));

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('player-character-page')
        ->assertSee('18/24 HP')
        ->assertSee('HEALTHY')
        ->assertSee('6/30 HP')
        ->assertSee('POISONED');
});

test('shows an empty party message when there are no other characters', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);
    $character = Character::factory()->create(['user_id' => $user->id, 'campaign_id' => $campaign->id]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('player-character-page')
        ->assertSee('My Party · 0')
        ->assertSee('No other characters in this campaign yet.');
});

test('refreshes the character and party when the campaign-switched event fires', function () {
    $user = User::factory()->create();
    $firstCampaign = Campaign::factory()->create();
    $secondCampaign = Campaign::factory()->create();
    $user->campaigns()->attach([$firstCampaign->id, $secondCampaign->id]);

    Character::factory()->create(['user_id' => $user->id, 'campaign_id' => $firstCampaign->id, 'name' => 'Thornwick']);
    Character::factory()->create(['user_id' => $user->id, 'campaign_id' => $secondCampaign->id, 'name' => 'Elowen']);

    session(['selected_campaign_id' => $firstCampaign->id]);

    $component = Livewire::actingAs($user)
        ->test('player-character-page')
        ->assertSee('Thornwick')
        ->assertDontSee('Elowen');

    // In production, campaign-selector-banner updates the session before dispatching
    // this event; simulate that here since we're dispatching it directly in the test.
    session(['selected_campaign_id' => $secondCampaign->id]);

    $component->dispatch('campaign-switched', campaignId: $secondCampaign->id)
        ->assertSee('Elowen')
        ->assertDontSee('Thornwick');
});

test('opening the quick edit modal prefills health and condition', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);
    $character = Character::factory()->create(['user_id' => $user->id, 'campaign_id' => $campaign->id]);
    $sheet = CharacterSheet::factory()->create(['character_id' => $character->id, 'health' => 20, 'total_health' => 40]);
    $poisoned = CharacterStatus::factory()->create(['name' => 'Poisoned']);
    $sheet->statuses()->attach($poisoned);
    $character->update(['character_sheet_id' => $sheet->id]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('player-character-page')
        ->call('openQuickEdit')
        ->assertSet('quickHealth', 20)
        ->assertSet('quickConditionIds', [$poisoned->id])
        ->assertSee('Poisoned');
});

test('the user can quick edit their health and condition', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);
    $character = Character::factory()->create(['user_id' => $user->id, 'campaign_id' => $campaign->id]);
    $sheet = CharacterSheet::factory()->create(['character_id' => $character->id, 'health' => 20, 'total_health' => 40]);
    $character->update(['character_sheet_id' => $sheet->id]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('player-character-page')
        ->call('openQuickEdit')
        ->set('quickHealth', 35)
        ->call('saveQuickEdit')
        ->assertHasNoErrors();

    expect($sheet->fresh())
        ->health->toBe(35);
});

test('the user can add a condition to their character', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);
    $character = Character::factory()->create(['user_id' => $user->id, 'campaign_id' => $campaign->id]);
    $sheet = CharacterSheet::factory()->create(['character_id' => $character->id, 'health' => 20, 'total_health' => 40]);
    $character->update(['character_sheet_id' => $sheet->id]);
    $poisoned = CharacterStatus::factory()->create(['name' => 'Poisoned']);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('player-character-page')
        ->call('openQuickEdit')
        ->set('quickConditionIds', [$poisoned->id])
        ->call('saveQuickEdit')
        ->assertHasNoErrors();

    expect($sheet->fresh()->statuses->pluck('id')->all())->toBe([$poisoned->id]);
});

test('the user can remove a condition from their character', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);
    $character = Character::factory()->create(['user_id' => $user->id, 'campaign_id' => $campaign->id]);
    $sheet = CharacterSheet::factory()->create(['character_id' => $character->id, 'health' => 20, 'total_health' => 40]);
    $sheet->statuses()->attach(CharacterStatus::factory()->create(['name' => 'Poisoned']));
    $character->update(['character_sheet_id' => $sheet->id]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('player-character-page')
        ->call('openQuickEdit')
        ->set('quickConditionIds', [])
        ->call('saveQuickEdit')
        ->assertHasNoErrors();

    expect($sheet->fresh()->statuses)->toBeEmpty();
});

test('quick edit rejects a condition id that does not exist', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);
    $character = Character::factory()->create(['user_id' => $user->id, 'campaign_id' => $campaign->id]);
    $sheet = CharacterSheet::factory()->create(['character_id' => $character->id, 'health' => 20, 'total_health' => 40]);
    $character->update(['character_sheet_id' => $sheet->id]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('player-character-page')
        ->call('openQuickEdit')
        ->set('quickConditionIds', [99999])
        ->call('saveQuickEdit')
        ->assertHasErrors(['quickConditionIds.0']);

    expect($sheet->fresh()->statuses)->toBeEmpty();
});

test('quick edit health cannot exceed max health', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);
    $character = Character::factory()->create(['user_id' => $user->id, 'campaign_id' => $campaign->id]);
    $sheet = CharacterSheet::factory()->create(['character_id' => $character->id, 'health' => 20, 'total_health' => 40]);
    $character->update(['character_sheet_id' => $sheet->id]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('player-character-page')
        ->call('openQuickEdit')
        ->set('quickHealth', 999)
        ->call('saveQuickEdit')
        ->assertHasErrors(['quickHealth']);

    expect($sheet->fresh()->health)->toBe(20);
});

test('quick edit creates a character sheet if the character has none', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);
    $character = Character::factory()->create(['user_id' => $user->id, 'campaign_id' => $campaign->id, 'character_sheet_id' => null]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('player-character-page')
        ->call('openQuickEdit')
        ->set('quickHealth', 50)
        ->call('saveQuickEdit')
        ->assertHasNoErrors();

    $character->refresh();

    expect($character->character_sheet)->not->toBeNull()
        ->and($character->character_sheet->health)->toBe(50);
});

test('opening the sheet edit modal prefills every field', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);
    $character = Character::factory()->create(['user_id' => $user->id, 'campaign_id' => $campaign->id]);
    $sheet = CharacterSheet::factory()->create([
        'character_id' => $character->id,
        'description' => 'A grizzled veteran.',
        'class' => 'fighter',
        'race' => 'dwarf',
        'level' => 7,
        'alignment' => 'lawful good',
        'background' => 'Soldier',
        'total_health' => 60,
        'ac' => 18,
    ]);
    $character->update(['character_sheet_id' => $sheet->id]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('player-character-page')
        ->call('openSheetEdit')
        ->assertSet('sheetDescription', 'A grizzled veteran.')
        ->assertSet('sheetClass', 'fighter')
        ->assertSet('sheetRace', 'dwarf')
        ->assertSet('sheetLevel', 7)
        ->assertSet('sheetAlignment', 'lawful good')
        ->assertSet('sheetBackground', 'Soldier')
        ->assertSet('sheetTotalHealth', 60)
        ->assertSet('sheetAc', 18);
});

test('the user can update their full character sheet', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);
    $character = Character::factory()->create(['user_id' => $user->id, 'campaign_id' => $campaign->id]);
    $sheet = CharacterSheet::factory()->create(['character_id' => $character->id]);
    $character->update(['character_sheet_id' => $sheet->id]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('player-character-page')
        ->call('openSheetEdit')
        ->set('sheetDescription', 'A grizzled veteran.')
        ->set('sheetClass', 'fighter')
        ->set('sheetRace', 'dwarf')
        ->set('sheetLevel', 7)
        ->set('sheetAlignment', 'lawful good')
        ->set('sheetBackground', 'Soldier')
        ->set('sheetTotalHealth', 60)
        ->set('sheetAc', 18)
        ->call('saveSheetEdit')
        ->assertHasNoErrors();

    expect($sheet->fresh())
        ->description->toBe('A grizzled veteran.')
        ->class->toBe('fighter')
        ->race->toBe('dwarf')
        ->level->toBe(7)
        ->alignment->toBe('lawful good')
        ->background->toBe('Soldier')
        ->total_health->toBe(60)
        ->ac->toBe(18);
});

test('lowering max health clamps current health down to the new max', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);
    $character = Character::factory()->create(['user_id' => $user->id, 'campaign_id' => $campaign->id]);
    $sheet = CharacterSheet::factory()->create(['character_id' => $character->id, 'health' => 38, 'total_health' => 42]);
    $character->update(['character_sheet_id' => $sheet->id]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('player-character-page')
        ->call('openSheetEdit')
        ->set('sheetTotalHealth', 20)
        ->call('saveSheetEdit')
        ->assertHasNoErrors();

    expect($sheet->fresh())
        ->total_health->toBe(20)
        ->health->toBe(20);
});

test('sheet edit rejects an invalid alignment', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);
    $character = Character::factory()->create(['user_id' => $user->id, 'campaign_id' => $campaign->id]);
    $sheet = CharacterSheet::factory()->create(['character_id' => $character->id, 'alignment' => 'true neutral']);
    $character->update(['character_sheet_id' => $sheet->id]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('player-character-page')
        ->call('openSheetEdit')
        ->set('sheetAlignment', 'evil overlord')
        ->call('saveSheetEdit')
        ->assertHasErrors(['sheetAlignment']);

    expect($sheet->fresh()->alignment)->toBe('true neutral');
});

test('sheet edit creates a character sheet if the character has none', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);
    $character = Character::factory()->create(['user_id' => $user->id, 'campaign_id' => $campaign->id, 'character_sheet_id' => null]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('player-character-page')
        ->call('openSheetEdit')
        ->set('sheetClass', 'wizard')
        ->set('sheetRace', 'gnome')
        ->set('sheetTotalHealth', 25)
        ->call('saveSheetEdit')
        ->assertHasNoErrors();

    $character->refresh();

    expect($character->character_sheet)->not->toBeNull()
        ->and($character->character_sheet->class)->toBe('wizard')
        ->and($character->character_sheet->race)->toBe('gnome')
        ->and($character->character_sheet->total_health)->toBe(25);
});
