<?php

use App\Models\Campaign;
use App\Models\User;
use Livewire\Livewire;

test('shows nothing when no campaign is selected', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('sidebar-dynamic-navigation')
        ->assertDontSee('Characters')
        ->assertDontSee('Character')
        ->assertDontSee('Scenes')
        ->assertDontSee('Inventory');
});

test('shows nothing for a campaign the user does not have access to', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('sidebar-dynamic-navigation')
        ->assertDontSee('Characters')
        ->assertDontSee('Scenes')
        ->assertDontSee('Inventory');
});

test('shows dungeon master navigation when the user owns the selected campaign', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id, 'name' => 'Crimson Vale']);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('sidebar-dynamic-navigation')
        ->assertSee('Crimson Vale')
        ->assertSee('Characters')
        ->assertSee('Shops')
        ->assertSee('Scenes')
        ->assertSee('Journal')
        ->assertDontSee('Inventory');
});

test('shows player navigation when the user is a member but not the owner', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id, 'name' => 'Port Namas']);
    $member->campaigns()->attach($campaign->id);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($member)
        ->test('sidebar-dynamic-navigation')
        ->assertSee('Port Namas')
        ->assertSee('Character')
        ->assertSee('Shops')
        ->assertSee('Journal')
        ->assertDontSee('Inventory')
        ->assertDontSee('Scenes')
        ->assertDontSee('Characters');
});

test('switches from player to dungeon master navigation when the campaign-switched event fires', function () {
    $user = User::factory()->create();
    $ownedCampaign = Campaign::factory()->create(['owner_id' => $user->id, 'name' => 'Crimson Vale']);

    $otherOwner = User::factory()->create();
    $memberCampaign = Campaign::factory()->create(['owner_id' => $otherOwner->id, 'name' => 'Port Namas']);
    $user->campaigns()->attach($memberCampaign->id);

    session(['selected_campaign_id' => $memberCampaign->id]);

    $component = Livewire::actingAs($user)
        ->test('sidebar-dynamic-navigation')
        ->assertSee('Port Namas')
        ->assertSee('Character')
        ->assertDontSee('Scenes');

    // In production, campaign-selector-banner updates the session before dispatching
    // this event; simulate that here since we're dispatching it directly in the test.
    session(['selected_campaign_id' => $ownedCampaign->id]);

    $component->dispatch('campaign-switched', campaignId: $ownedCampaign->id)
        ->assertSee('Crimson Vale')
        ->assertSee('Scenes')
        ->assertDontSee('Inventory');
});
