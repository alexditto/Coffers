<?php

use App\Models\Campaign;
use App\Models\User;
use Livewire\Livewire;

test('shows nothing when no campaign is selected', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('mobile-bottom-navigation')
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
        ->test('mobile-bottom-navigation')
        ->assertDontSee('Characters')
        ->assertDontSee('Scenes')
        ->assertDontSee('Inventory');
});

test('shows dungeon master icons when the user owns the selected campaign', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('mobile-bottom-navigation')
        ->assertSee('Characters')
        ->assertSee('Shops')
        ->assertSee('Scenes')
        ->assertSee('Journal')
        ->assertDontSee('Inventory');
});

test('shows player icons without inventory, which now lives on the character page', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $member->campaigns()->attach($campaign->id);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($member)
        ->test('mobile-bottom-navigation')
        ->assertSee('Character')
        ->assertSee('Shops')
        ->assertSee('Journal')
        ->assertDontSee('Inventory')
        ->assertDontSee('Scenes')
        ->assertDontSee('Characters');
});

test('switches from player to dungeon master icons when the campaign-switched event fires', function () {
    $user = User::factory()->create();
    $ownedCampaign = Campaign::factory()->create(['owner_id' => $user->id]);

    $otherOwner = User::factory()->create();
    $memberCampaign = Campaign::factory()->create(['owner_id' => $otherOwner->id]);
    $user->campaigns()->attach($memberCampaign->id);

    session(['selected_campaign_id' => $memberCampaign->id]);

    $component = Livewire::actingAs($user)
        ->test('mobile-bottom-navigation')
        ->assertSee('Character')
        ->assertDontSee('Scenes');

    // In production, campaign-selector-banner updates the session before dispatching
    // this event; simulate that here since we're dispatching it directly in the test.
    session(['selected_campaign_id' => $ownedCampaign->id]);

    $component->dispatch('campaign-switched', campaignId: $ownedCampaign->id)
        ->assertSee('Scenes')
        ->assertDontSee('Inventory');
});
