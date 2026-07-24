<?php

use App\Models\Campaign;
use App\Models\User;
use Livewire\Livewire;

test('shows an empty state when no campaign is selected', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('shop-page')
        ->assertSee('Select or create a campaign');
});

test('shows an empty state for a campaign the user does not have access to', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('shop-page')
        ->assertSee('Select or create a campaign');
});

test('shows the dungeon master shop page when the user owns the selected campaign', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id, 'name' => 'Crimson Vale']);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('shop-page')
        ->assertSee('+ New shop')
        ->assertDontSee('Browse the shops');
});

test('shows the player shop page when the user is a member but not the owner', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id, 'name' => 'Port Namas']);
    $member->campaigns()->attach($campaign->id);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($member)
        ->test('shop-page')
        ->assertSee('Port Namas')
        ->assertSee('Browse the shops')
        ->assertDontSee('Manage the shops');
});

test('switches from player to dungeon master shop page when the campaign-switched event fires', function () {
    $user = User::factory()->create();
    $ownedCampaign = Campaign::factory()->create(['owner_id' => $user->id, 'name' => 'Crimson Vale']);

    $otherOwner = User::factory()->create();
    $memberCampaign = Campaign::factory()->create(['owner_id' => $otherOwner->id, 'name' => 'Port Namas']);
    $user->campaigns()->attach($memberCampaign->id);

    session(['selected_campaign_id' => $memberCampaign->id]);

    $component = Livewire::actingAs($user)
        ->test('shop-page')
        ->assertSee('Browse the shops');

    // In production, campaign-selector-banner updates the session before dispatching
    // this event; simulate that here since we're dispatching it directly in the test.
    session(['selected_campaign_id' => $ownedCampaign->id]);

    $component->dispatch('campaign-switched', campaignId: $ownedCampaign->id)
        ->assertSee('+ New shop')
        ->assertDontSee('Browse the shops');
});

test('guests are redirected to the login page', function () {
    $this->get(route('shops'))->assertRedirect(route('login'));
});

test('authenticated users can visit the shops page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('shops'))->assertOk();
});
