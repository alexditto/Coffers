<?php

use App\Models\Campaign;
use App\Models\User;
use Livewire\Livewire;

test('shows an empty state when no campaign is selected', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('campaign-details')
        ->assertSee('Select or create a campaign');
});

test('does not show a campaign the user does not belong to', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('campaign-details')
        ->assertSee('Select or create a campaign')
        ->assertDontSee($campaign->name);
});

test('members see read only campaign details without an edit form', function () {
    $owner = User::factory()->create(['name' => 'Gary Gygax']);
    $member = User::factory()->create();
    $campaign = Campaign::factory()->create(['name' => 'Port Namas', 'owner_id' => $owner->id]);
    $member->campaigns()->attach($campaign->id);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($member)
        ->test('campaign-details')
        ->assertSee('Port Namas')
        ->assertSee('Gary Gygax')
        ->assertDontSee('Save changes');
});

test('the owner can update the campaign details', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id, 'status' => 'active']);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('campaign-details')
        ->assertSee('Save changes')
        ->set('name', 'Crimson Vale')
        ->set('description', 'A haunted valley campaign.')
        ->set('status', 'inactive')
        ->set('nextSessionDate', now()->addWeek()->format('Y-m-d'))
        ->call('updateDetails')
        ->assertHasNoErrors();

    expect($campaign->fresh())
        ->name->toBe('Crimson Vale')
        ->description->toBe('A haunted valley campaign.')
        ->status->toBe('inactive');

    expect($campaign->fresh()->next_session_date->format('Y-m-d'))->toBe(now()->addWeek()->format('Y-m-d'));
});

test('a non owner member cannot update campaign details', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id, 'name' => 'Untouched']);
    $member->campaigns()->attach($campaign->id);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($member)
        ->test('campaign-details')
        ->set('name', 'Hacked Name')
        ->call('updateDetails')
        ->assertForbidden();

    expect($campaign->fresh()->name)->toBe('Untouched');
});

test('updates when the selected campaign changes', function () {
    $owner = User::factory()->create();
    $first = Campaign::factory()->create(['name' => 'Port Namas', 'owner_id' => $owner->id]);
    $second = Campaign::factory()->create(['name' => 'Crimson Vale', 'owner_id' => $owner->id]);

    session(['selected_campaign_id' => $first->id]);

    Livewire::actingAs($owner)
        ->test('campaign-details')
        ->assertSee('Port Namas')
        ->dispatch('campaign-switched', campaignId: $second->id)
        ->assertSee('Crimson Vale')
        ->assertDontSee('Port Namas');
});
