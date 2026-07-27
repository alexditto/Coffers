<?php

use App\Models\Campaign;
use App\Models\User;
use Livewire\Livewire;

test('shows a call to action when the user has no campaigns', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('campaign-selector-banner')
        ->assertSee('Create your first campaign');
});

test('defaults to the users first campaign', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign);

    Livewire::actingAs($user)
        ->test('campaign-selector-banner')
        ->assertSee($campaign->name)
        ->assertSet('selectedCampaignId', $campaign->id);
});

test('can switch the selected campaign and persists it to the session', function () {
    $user = User::factory()->create();
    $first = Campaign::factory()->create(['name' => 'Alpha Campaign']);
    $second = Campaign::factory()->create(['name' => 'Beta Campaign']);
    $user->campaigns()->attach([$first->id, $second->id]);

    Livewire::actingAs($user)
        ->test('campaign-selector-banner')
        ->assertSet('selectedCampaignId', $first->id)
        ->call('selectCampaign', $second->id)
        ->assertSet('selectedCampaignId', $second->id);

    expect(session('selected_campaign_id'))->toBe($second->id);
});

test('can create a new campaign and it becomes selected', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('campaign-selector-banner')
        ->set('newCampaignName', 'Crimson Vale')
        ->set('newCampaignDescription', 'A haunted valley campaign.')
        ->call('createCampaign')
        ->assertHasNoErrors();

    $campaign = Campaign::where('name', 'Crimson Vale')->firstOrFail();

    expect($campaign->owner_id)->toBe($user->id)
        ->and($user->campaigns()->where('campaign_id', $campaign->id)->exists())->toBeTrue()
        ->and(session('selected_campaign_id'))->toBe($campaign->id);
});

test('requires a name to create a campaign', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('campaign-selector-banner')
        ->set('newCampaignName', '')
        ->call('createCampaign')
        ->assertHasErrors(['newCampaignName' => 'required']);
});

test('each rendered instance gets its own unique create-campaign modal name', function () {
    $user = User::factory()->create();

    // Every authenticated page embeds this component more than once (a compact
    // copy in the mobile header, a full copy in page content for desktop),
    // toggled with responsive CSS rather than conditional rendering - both exist
    // in the DOM at once. A shared literal modal name would collide between them.
    $first = Livewire::actingAs($user)->test('campaign-selector-banner');
    $second = Livewire::actingAs($user)->test('campaign-selector-banner');

    $firstName = $first->instance()->createCampaignModalName();
    $secondName = $second->instance()->createCampaignModalName();

    expect($firstName)->not->toBe($secondName);

    $first->assertSeeHtml('data-modal="'.$firstName.'"');
    $second->assertSeeHtml('data-modal="'.$secondName.'"');
});
