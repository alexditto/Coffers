<?php

use App\Models\Campaign;
use App\Models\Scene;
use App\Models\User;
use Livewire\Livewire;

test('shows an empty state when no campaign is selected', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('scenes-page')
        ->assertSee('Select or create a campaign');
});

test('shows an empty state for a campaign the user does not have access to', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('scenes-page')
        ->assertSee('Select or create a campaign');
});

test('a non owner member sees a restricted message instead of scene controls', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $member->campaigns()->attach($campaign->id);
    Scene::factory()->create(['campaign_id' => $campaign->id, 'status' => 'active', 'name' => 'Port Namas']);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($member)
        ->test('scenes-page')
        ->assertSee('Only the Dungeon Master can manage scenes.')
        ->assertDontSee('Port Namas')
        ->assertDontSee('New scene');
});

test('shows no active scene message when the campaign has no scenes', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('scenes-page')
        ->assertSee('No active scene');
});

test('the first scene created for a campaign becomes active automatically', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('scenes-page')
        ->call('newScene')
        ->set('sceneName', 'Port Namas — The Docks')
        ->call('saveScene')
        ->assertHasNoErrors();

    $scene = Scene::where('name', 'Port Namas — The Docks')->firstOrFail();

    expect($scene->status)->toBe('active');
});

test('a second scene created for a campaign does not become active automatically', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    Scene::factory()->create(['campaign_id' => $campaign->id, 'status' => 'active', 'name' => 'The Docks']);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('scenes-page')
        ->call('newScene')
        ->set('sceneName', 'The Sunken Crypt')
        ->call('saveScene')
        ->assertHasNoErrors();

    $scene = Scene::where('name', 'The Sunken Crypt')->firstOrFail();

    expect($scene->status)->toBe('inactive');
});

test('the owner can switch the active scene, deactivating the previous one', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $current = Scene::factory()->create(['campaign_id' => $campaign->id, 'status' => 'active']);
    $other = Scene::factory()->create(['campaign_id' => $campaign->id, 'status' => 'inactive']);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('scenes-page')
        ->call('setActiveScene', $other->id);

    expect($other->fresh()->status)->toBe('active')
        ->and($current->fresh()->status)->toBe('inactive');
});

test('the owner can edit a scene', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $scene = Scene::factory()->create(['campaign_id' => $campaign->id, 'name' => 'Old Name']);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('scenes-page')
        ->call('editScene', $scene->id)
        ->assertSet('sceneName', 'Old Name')
        ->set('sceneName', 'Port Namas — The Docks')
        ->set('sceneContent', 'A rain-slicked pier at the edge of town.')
        ->call('saveScene')
        ->assertHasNoErrors();

    expect($scene->fresh())
        ->name->toBe('Port Namas — The Docks')
        ->content->toBe('A rain-slicked pier at the edge of town.');
});

test('a non owner cannot mutate scenes', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $member->campaigns()->attach($campaign->id);
    $scene = Scene::factory()->create(['campaign_id' => $campaign->id, 'status' => 'inactive']);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($member)->test('scenes-page')->call('setActiveScene', $scene->id)->assertForbidden();
    Livewire::actingAs($member)->test('scenes-page')->call('editScene', $scene->id)->assertForbidden();
    Livewire::actingAs($member)->test('scenes-page')->call('newScene')->assertForbidden();

    expect($scene->fresh()->status)->toBe('inactive');
});

test('cannot mutate a scene belonging to a different campaign by guessing its id', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $otherScene = Scene::factory()->create(['status' => 'inactive']);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('scenes-page')
        ->call('setActiveScene', $otherScene->id);

    expect($otherScene->fresh()->status)->toBe('inactive');
});

test('refreshes the scene list when the campaign-switched event fires', function () {
    $owner = User::factory()->create();
    $firstCampaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $secondCampaign = Campaign::factory()->create(['owner_id' => $owner->id]);

    // Deliberately avoiding "The Docks" - it also appears in this component's
    // "New scene" placeholder text, which would make assertDontSee() a false negative.
    Scene::factory()->create(['campaign_id' => $firstCampaign->id, 'name' => 'Sunless Harbor', 'status' => 'active']);
    Scene::factory()->create(['campaign_id' => $secondCampaign->id, 'name' => 'Highmoor Keep', 'status' => 'active']);

    session(['selected_campaign_id' => $firstCampaign->id]);

    $component = Livewire::actingAs($owner)
        ->test('scenes-page')
        ->assertSee('Sunless Harbor')
        ->assertDontSee('Highmoor Keep');

    // In production, campaign-selector-banner updates the session before dispatching
    // this event; simulate that here since we're dispatching it directly in the test.
    session(['selected_campaign_id' => $secondCampaign->id]);

    $component->dispatch('campaign-switched', campaignId: $secondCampaign->id)
        ->assertSee('Highmoor Keep')
        ->assertDontSee('Sunless Harbor');
});
