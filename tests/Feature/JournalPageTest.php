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

test('defaults to the All tab, showing entries of every type', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    Journal::factory()->create(['campaign_id' => $campaign->id, 'type' => 'npc', 'title' => 'Dockmaster Hale', 'revealed' => true]);
    Journal::factory()->create(['campaign_id' => $campaign->id, 'type' => 'lore', 'title' => 'The Old Kingdom', 'revealed' => true]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('journal-page')
        ->assertSee('Dockmaster Hale')
        ->assertSee('The Old Kingdom')
        ->call('setTypeFilter', 'npc')
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

test('a player only ever receives entries the dm has revealed or that they authored themselves', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $member->campaigns()->attach($campaign->id);

    Journal::factory()->create(['campaign_id' => $campaign->id, 'user_id' => $owner->id, 'type' => 'npc', 'title' => 'Dockmaster Hale', 'revealed' => true]);
    Journal::factory()->create(['campaign_id' => $campaign->id, 'user_id' => $owner->id, 'type' => 'npc', 'title' => 'The Masked Broker', 'revealed' => false]);
    Journal::factory()->create(['campaign_id' => $campaign->id, 'user_id' => $member->id, 'type' => 'npc', 'title' => 'My Private Note', 'revealed' => false]);

    session(['selected_campaign_id' => $campaign->id]);

    $component = Livewire::actingAs($member)
        ->test('journal-page')
        ->assertSee('Dockmaster Hale')
        ->assertDontSee('The Masked Broker')
        ->assertSee('My Private Note')
        ->assertSee('New entry');

    expect($component->get('entries')->pluck('title')->all())->toEqualCanonicalizing(['Dockmaster Hale', 'My Private Note']);
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

test('a player cannot reveal, hide, or edit an entry they did not author', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $member->campaigns()->attach($campaign->id);
    $entry = Journal::factory()->create(['campaign_id' => $campaign->id, 'user_id' => $owner->id, 'revealed' => false]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($member)->test('journal-page')->call('toggleReveal', $entry->id)->assertForbidden();
    Livewire::actingAs($member)->test('journal-page')->call('editEntry', $entry->id)->assertForbidden();

    expect($entry->fresh()->revealed)->toBeFalse();
});

test('a player can create their own journal entry, which starts private', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $member->campaigns()->attach($campaign->id);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($member)
        ->test('journal-page')
        ->call('newEntry')
        ->set('entryTitle', 'My Backstory')
        ->set('entryType', 'lore')
        ->set('entryContent', 'A quiet secret from my past.')
        ->call('saveEntry')
        ->assertHasNoErrors();

    $entry = Journal::where('title', 'My Backstory')->firstOrFail();

    expect($entry->revealed)->toBeFalse()
        ->and($entry->user_id)->toBe($member->id);
});

test('a player can share and go private on their own entry, labeled Share and Private', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $member->campaigns()->attach($campaign->id);
    $entry = Journal::factory()->create(['campaign_id' => $campaign->id, 'user_id' => $member->id, 'title' => 'My Backstory', 'revealed' => false]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($member)
        ->test('journal-page')
        ->assertSee('Private — tap to share')
        ->assertSee('Share')
        ->call('toggleReveal', $entry->id)
        ->assertSee('Shared with party')
        ->assertSee('Private');

    expect($entry->fresh()->revealed)->toBeTrue();
});

test('the owner can edit or reveal a players entry too, but a player cannot manage another players entry', function () {
    $owner = User::factory()->create();
    $memberOne = User::factory()->create();
    $memberTwo = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $memberOne->campaigns()->attach($campaign->id);
    $memberTwo->campaigns()->attach($campaign->id);
    $entry = Journal::factory()->create(['campaign_id' => $campaign->id, 'user_id' => $memberOne->id, 'revealed' => false]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)->test('journal-page')->call('toggleReveal', $entry->id);
    expect($entry->fresh()->revealed)->toBeTrue();

    Livewire::actingAs($memberTwo)->test('journal-page')->call('toggleReveal', $entry->id)->assertForbidden();
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

test('clicking an entry opens a details modal with its content', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $entry = Journal::factory()->create([
        'campaign_id' => $campaign->id,
        'title' => 'Dockmaster Hale',
        'type' => 'npc',
        'content' => 'Runs the docks and knows everyone worth knowing.',
        'revealed' => true,
    ]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('journal-page')
        ->assertDontSeeHtml('wire:click="confirmDeleteEntry')
        ->call('viewEntry', $entry->id)
        ->assertSee('Dockmaster Hale')
        ->assertSee('Runs the docks')
        ->assertSee('NPCs')
        ->assertSee('Revealed to party');
});

test('a player sees who authored an entry they cannot manage', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $member->campaigns()->attach($campaign->id);
    $entry = Journal::factory()->create(['campaign_id' => $campaign->id, 'user_id' => $owner->id, 'title' => 'Dockmaster Hale', 'revealed' => true]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($member)
        ->test('journal-page')
        ->call('viewEntry', $entry->id)
        ->assertSee('Written by')
        ->assertSee($owner->name);
});

test('a player cannot view an entry that is hidden from them', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $member->campaigns()->attach($campaign->id);
    $entry = Journal::factory()->create(['campaign_id' => $campaign->id, 'user_id' => $owner->id, 'revealed' => false]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($member)
        ->test('journal-page')
        ->call('viewEntry', $entry->id)
        ->assertForbidden();
});

test('the owner can delete an entry from the edit modal', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $entry = Journal::factory()->create(['campaign_id' => $campaign->id, 'title' => 'Dockmaster Hale']);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('journal-page')
        ->call('editEntry', $entry->id)
        ->assertSeeHtml('wire:click="confirmDeleteEntry')
        ->call('confirmDeleteEntry')
        ->assertSee('Delete entry')
        ->assertSee('Dockmaster Hale')
        ->call('deleteEntry');

    expect(Journal::find($entry->id))->toBeNull();
});

test('a player can delete their own entry but not one authored by someone else', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $member->campaigns()->attach($campaign->id);

    $ownEntry = Journal::factory()->create(['campaign_id' => $campaign->id, 'user_id' => $member->id]);
    $othersEntry = Journal::factory()->create(['campaign_id' => $campaign->id, 'user_id' => $owner->id]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($member)->test('journal-page')->call('editEntry', $othersEntry->id)->assertForbidden();

    Livewire::actingAs($member)
        ->test('journal-page')
        ->call('editEntry', $ownEntry->id)
        ->call('confirmDeleteEntry')
        ->call('deleteEntry');

    expect(Journal::find($ownEntry->id))->toBeNull();
    expect(Journal::find($othersEntry->id))->not->toBeNull();
});

test('cannot delete an entry belonging to a different campaign by guessing its id', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $otherEntry = Journal::factory()->create();

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('journal-page')
        ->set('editingEntryId', $otherEntry->id)
        ->call('confirmDeleteEntry');

    expect(Journal::find($otherEntry->id))->not->toBeNull();
});

test('search filters entries by title or content', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    Journal::factory()->create(['campaign_id' => $campaign->id, 'title' => 'Dockmaster Hale', 'content' => 'Runs the docks.', 'revealed' => true]);
    Journal::factory()->create(['campaign_id' => $campaign->id, 'title' => 'The Old Kingdom', 'content' => 'A fallen empire, once ruled by the Dockmaster\'s ancestors.', 'revealed' => true]);
    Journal::factory()->create(['campaign_id' => $campaign->id, 'title' => 'Potion Recipe', 'content' => 'Mix mushrooms with moonlight.', 'revealed' => true]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('journal-page')
        ->set('search', 'dockmaster')
        ->assertSee('Dockmaster Hale')
        ->assertSee('The Old Kingdom')
        ->assertDontSee('Potion Recipe');
});

test('shows a no results message when the search does not match anything', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    Journal::factory()->create(['campaign_id' => $campaign->id, 'title' => 'Dockmaster Hale', 'revealed' => true]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('journal-page')
        ->set('search', 'nonexistent entry')
        ->assertSee('No entries match')
        ->assertSee('nonexistent entry');
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
