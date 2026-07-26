<?php

use App\Models\Campaign;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('campaign-builder'))->assertRedirect(route('login'));
});

test('authenticated users can visit the campaign builder page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('campaign-builder'))->assertOk();
});

test('selecting a campaign via the query string updates the session when the user has access', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create(['name' => 'Port Namas']);
    $user->campaigns()->attach($campaign->id);

    $response = $this->actingAs($user)->get(route('campaign-builder', ['campaign' => $campaign->id]));

    $response->assertOk()->assertSee('Port Namas');
    expect(session('selected_campaign_id'))->toBe($campaign->id);
});

test('ignores a campaign query string id the user does not have access to', function () {
    $user = User::factory()->create();
    $ownCampaign = Campaign::factory()->create();
    $user->campaigns()->attach($ownCampaign->id);

    $inaccessibleCampaign = Campaign::factory()->create();

    session(['selected_campaign_id' => $ownCampaign->id]);

    $this->actingAs($user)->get(route('campaign-builder', ['campaign' => $inaccessibleCampaign->id]));

    expect(session('selected_campaign_id'))->toBe($ownCampaign->id);
});

test('the new campaign button triggers the create campaign modal', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('campaign-builder'))
        ->assertOk()
        ->assertSee('New Campaign')
        ->assertSeeInOrder([
            'data-flux-modal-trigger',
            "name: 'create-campaign'",
            'New Campaign',
        ], false);
});
