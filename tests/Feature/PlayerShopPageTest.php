<?php

use App\Models\Campaign;
use App\Models\Shop;
use App\Models\User;
use Livewire\Livewire;

test('shows an empty state when no campaign is selected', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('player-shop-page')
        ->assertSee('Select or create a campaign');
});

test('shows an empty state for a campaign the user does not have access to', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('player-shop-page')
        ->assertSee('Select or create a campaign');
});

test('shows an empty state when the campaign has no visible shops', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('player-shop-page')
        ->assertSee('No shops discovered in this campaign yet.');
});

test('groups shops into open, closed, and unknown sections and excludes drafts entirely', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);

    $open = Shop::factory()->create(['status' => 'open', 'name' => 'The Iron Anvil']);
    $open->campaigns()->attach($campaign->id);

    $closed = Shop::factory()->create(['status' => 'closed', 'name' => 'Salty Mermaid Tavern']);
    $closed->campaigns()->attach($campaign->id);

    $hidden = Shop::factory()->create(['status' => 'hidden', 'name' => 'The Masked Bazaar']);
    $hidden->campaigns()->attach($campaign->id);

    $draft = Shop::factory()->create(['status' => 'draft', 'name' => 'Unfinished Shop']);
    $draft->campaigns()->attach($campaign->id);

    session(['selected_campaign_id' => $campaign->id]);

    $component = Livewire::actingAs($user)
        ->test('player-shop-page')
        ->assertSee('Open · 1')
        ->assertSee('The Iron Anvil')
        ->assertSee('Closed · 1')
        ->assertSee('Salty Mermaid Tavern')
        ->assertSee('Unknown · 1')
        ->assertSee('??????')
        // The hidden shop's real name must never leak to the player, only the placeholder.
        ->assertDontSee('The Masked Bazaar')
        ->assertDontSee('Unfinished Shop');

    expect($component->get('openShops')->pluck('name')->all())->toBe(['The Iron Anvil'])
        ->and($component->get('closedShops')->pluck('name')->all())->toBe(['Salty Mermaid Tavern'])
        ->and($component->get('unknownShops')->pluck('name')->all())->toBe(['The Masked Bazaar']);
});

test('an open shop links to its shop detail page', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);
    $shop = Shop::factory()->create(['status' => 'open']);
    $shop->campaigns()->attach($campaign->id);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('player-shop-page')
        ->assertSeeHtml(route('shop-detail', ['shop' => $shop->id]));
});

test('a closed shop is not linked and shows a closed badge', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);
    $shop = Shop::factory()->create(['status' => 'closed']);
    $shop->campaigns()->attach($campaign->id);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('player-shop-page')
        ->assertSee('Closed')
        ->assertDontSeeHtml(route('shop-detail', ['shop' => $shop->id]));
});

test('refreshes the shop groups when the campaign-switched event fires', function () {
    $user = User::factory()->create();
    $firstCampaign = Campaign::factory()->create();
    $secondCampaign = Campaign::factory()->create();
    $user->campaigns()->attach([$firstCampaign->id, $secondCampaign->id]);

    $firstShop = Shop::factory()->create(['status' => 'open', 'name' => 'The Iron Anvil']);
    $firstShop->campaigns()->attach($firstCampaign->id);

    $secondShop = Shop::factory()->create(['status' => 'open', 'name' => 'Moonleaf Apothecary']);
    $secondShop->campaigns()->attach($secondCampaign->id);

    session(['selected_campaign_id' => $firstCampaign->id]);

    $component = Livewire::actingAs($user)
        ->test('player-shop-page')
        ->assertSee('The Iron Anvil')
        ->assertDontSee('Moonleaf Apothecary');

    // In production, campaign-selector-banner updates the session before dispatching
    // this event; simulate that here since we're dispatching it directly in the test.
    session(['selected_campaign_id' => $secondCampaign->id]);

    $component->dispatch('campaign-switched', campaignId: $secondCampaign->id)
        ->assertSee('Moonleaf Apothecary')
        ->assertDontSee('The Iron Anvil');
});

test('registers echo listeners scoped to the selected campaign for shop open and close broadcasts', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);

    session(['selected_campaign_id' => $campaign->id]);

    $listeners = Livewire::actingAs($user)
        ->test('player-shop-page')
        ->instance()
        ->getListeners();

    expect($listeners)->toHaveKey("echo-private:campaign.{$campaign->id}.shops,ShopOpened")
        ->and($listeners)->toHaveKey("echo-private:campaign.{$campaign->id}.shops,ShopClosed");
});

test('registers no echo listeners when no campaign is selected', function () {
    $user = User::factory()->create();

    $listeners = Livewire::actingAs($user)
        ->test('player-shop-page')
        ->instance()
        ->getListeners();

    expect($listeners)->toBe([]);
});

test('a shop opening elsewhere is reflected after a shop status changed broadcast is received', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);
    $shop = Shop::factory()->create(['status' => 'draft', 'name' => 'The Iron Anvil']);
    $shop->campaigns()->attach($campaign->id);

    session(['selected_campaign_id' => $campaign->id]);

    $component = Livewire::actingAs($user)
        ->test('player-shop-page')
        ->assertDontSee('The Iron Anvil');

    // Drafts are never player-visible - open it up as the DM would.
    $shop->update(['status' => 'open']);

    $component->call('onShopStatusChanged')
        ->assertSee('The Iron Anvil');
});
