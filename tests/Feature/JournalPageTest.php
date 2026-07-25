<?php

use App\Models\Campaign;
use App\Models\Journal;
use App\Models\User;
use Livewire\Livewire;

test('shows an empty state when no campaign is selected', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('journal-page')
        ->assertSee('Select or create a campaign');
});

test('shows an empty state for a campaign the user does not have access to', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('journal-page')
        ->assertSee('Select or create a campaign');
});

test('defaults to the NPCs tab', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    Journal::factory()->create(['campaign_id' => $campaign->id, 'type' => 'npc', 'title' => 'Dockmaster Hale', 'revealed' => true]);
    Journal::factory()->create(['campaign_id' => $campaign->id, 'type' => 'lore', 'title' => 'The Old Kingdom', 'revealed' => true]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('journal-page')
        ->assertSee('Dockmaster Hale')
        ->assertDontSee('The Old Kingdom');
});

test('the owner sees both revealed and hidden entries with their reveal state', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    Journal::factory()->create(['campaign_id' => $campaign->id, 'type' => 'npc', 'title' => 'Dockmaster Hale', 'revealed' => true]);
    Journal::factory()->create(['campaign_id' => $campaign->id, 'type' => 'npc', 'title' => 'The Masked Broker', 'revealed' => false]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('journal-page')
        ->assertSee('Dockmaster Hale')
        ->assertSee('Revealed to party')
        ->assertSee('The Masked Broker')
        ->assertSee('Hidden — tap to reveal');
});

test('a player only ever receives entries the dm has revealed', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $member->campaigns()->attach($campaign->id);

    Journal::factory()->create(['campaign_id' => $campaign->id, 'type' => 'npc', 'title' => 'Dockmaster Hale', 'revealed' => true]);
    Journal::factory()->create(['campaign_id' => $campaign->id, 'type' => 'npc', 'title' => 'The Masked Broker', 'revealed' => false]);

    session(['selected_campaign_id' => $campaign->id]);

    $component = Livewire::actingAs($member)
        ->test('journal-page')
        ->assertSee('Dockmaster Hale')
        ->assertDontSee('The Masked Broker')
        ->assertDontSee('New entry');

    expect($component->get('entries')->pluck('title')->all())->toBe(['Dockmaster Hale']);
});

test('the owner can reveal and hide an entry', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $entry = Journal::factory()->create(['campaign_id' => $campaign->id, 'type' => 'npc', 'revealed' => false]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('journal-page')
        ->call('toggleReveal', $entry->id);

    expect($entry->fresh()->revealed)->toBeTrue();

    Livewire::actingAs($owner)
        ->test('journal-page')
        ->call('toggleReveal', $entry->id);

    expect($entry->fresh()->revealed)->toBeFalse();
});

test('the owner can create a new entry which starts hidden', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('journal-page')
        ->call('newEntry')
        ->set('entryTitle', 'Dockmaster Hale')
        ->set('entryType', 'npc')
        ->set('entryContent', 'Runs the docks and knows everyone worth knowing.')
        ->call('saveEntry')
        ->assertHasNoErrors();

    $entry = Journal::where('title', 'Dockmaster Hale')->firstOrFail();

    expect($entry->revealed)->toBeFalse()
        ->and($entry->type)->toBe('npc');
});

test('the owner can edit an entry', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $entry = Journal::factory()->create(['campaign_id' => $campaign->id, 'title' => 'Old Title', 'type' => 'npc']);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('journal-page')
        ->call('editEntry', $entry->id)
        ->assertSet('entryTitle', 'Old Title')
        ->set('entryTitle', 'Dockmaster Hale')
        ->set('entryType', 'quest')
        ->call('saveEntry')
        ->assertHasNoErrors();

    expect($entry->fresh())
        ->title->toBe('Dockmaster Hale')
        ->type->toBe('quest');
});

test('a non owner cannot reveal, hide, or save entries', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $member->campaigns()->attach($campaign->id);
    $entry = Journal::factory()->create(['campaign_id' => $campaign->id, 'revealed' => false]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($member)->test('journal-page')->call('toggleReveal', $entry->id)->assertForbidden();
    Livewire::actingAs($member)->test('journal-page')->call('editEntry', $entry->id)->assertForbidden();
    Livewire::actingAs($member)->test('journal-page')->call('newEntry')->assertForbidden();

    expect($entry->fresh()->revealed)->toBeFalse();
});

test('cannot mutate an entry belonging to a different campaign by guessing its id', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $otherEntry = Journal::factory()->create(['revealed' => false]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('journal-page')
        ->call('toggleReveal', $otherEntry->id);

    expect($otherEntry->fresh()->revealed)->toBeFalse();
});

test('refreshes the entry list when the campaign-switched event fires', function () {
    $owner = User::factory()->create();
    $firstCampaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $secondCampaign = Campaign::factory()->create(['owner_id' => $owner->id]);

    Journal::factory()->create(['campaign_id' => $firstCampaign->id, 'type' => 'npc', 'title' => 'Captain Vorne', 'revealed' => true]);
    Journal::factory()->create(['campaign_id' => $secondCampaign->id, 'type' => 'npc', 'title' => 'Baroness Thale', 'revealed' => true]);

    session(['selected_campaign_id' => $firstCampaign->id]);

    $component = Livewire::actingAs($owner)
        ->test('journal-page')
        ->assertSee('Captain Vorne')
        ->assertDontSee('Baroness Thale');

    // In production, campaign-selector-banner updates the session before dispatching
    // this event; simulate that here since we're dispatching it directly in the test.
    session(['selected_campaign_id' => $secondCampaign->id]);

    $component->dispatch('campaign-switched', campaignId: $secondCampaign->id)
        ->assertSee('Baroness Thale')
        ->assertDontSee('Captain Vorne');
});
