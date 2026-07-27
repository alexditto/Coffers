<?php

use App\Events\ShopClosed;
use App\Events\ShopOpened;
use App\Models\Campaign;
use App\Models\Item;
use App\Models\Shop;
use App\Models\ShopStock;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

test('shows only open shops for the selected campaign by default', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $otherCampaign = Campaign::factory()->create(['owner_id' => $owner->id]);

    $openShop = Shop::factory()->create(['owner_id' => $owner->id, 'status' => 'open', 'name' => 'The Iron Anvil']);
    $openShop->campaigns()->attach($campaign->id);

    $closedShop = Shop::factory()->create(['owner_id' => $owner->id, 'status' => 'closed', 'name' => 'Moonleaf Apothecary']);
    $closedShop->campaigns()->attach($campaign->id);

    $otherCampaignShop = Shop::factory()->create(['owner_id' => $owner->id, 'status' => 'open', 'name' => 'Salty Mermaid Tavern']);
    $otherCampaignShop->campaigns()->attach($otherCampaign->id);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('d-m-shop-page')
        ->assertSee('The Iron Anvil')
        ->assertDontSee('Moonleaf Apothecary')
        ->assertDontSee('Salty Mermaid Tavern');
});

test('switching the tab filters shops by status', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);

    $draftShop = Shop::factory()->create(['owner_id' => $owner->id, 'status' => 'draft', 'name' => 'The Hidden Vault']);
    $draftShop->campaigns()->attach($campaign->id);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('d-m-shop-page')
        ->assertDontSee('The Hidden Vault')
        ->call('setStatusFilter', 'draft')
        ->assertSee('The Hidden Vault');
});

test('the owner can close and open a shop', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $shop = Shop::factory()->create(['owner_id' => $owner->id, 'status' => 'open']);
    $shop->campaigns()->attach($campaign->id);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('d-m-shop-page')
        ->call('closeShop', $shop->id);

    expect($shop->fresh()->status)->toBe('closed');

    Livewire::actingAs($owner)
        ->test('d-m-shop-page')
        ->call('setStatusFilter', 'closed')
        ->call('openShop', $shop->id);

    expect($shop->fresh()->status)->toBe('open');
});

test('closing an open shop broadcasts a ShopClosed event', function () {
    Event::fake([ShopClosed::class, ShopOpened::class]);

    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $shop = Shop::factory()->create(['owner_id' => $owner->id, 'status' => 'open']);
    $shop->campaigns()->attach($campaign->id);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('d-m-shop-page')
        ->call('closeShop', $shop->id);

    Event::assertDispatched(ShopClosed::class, fn (ShopClosed $event) => $event->shop->id === $shop->id);
    Event::assertNotDispatched(ShopOpened::class);
});

test('opening a closed shop broadcasts a ShopOpened event', function () {
    Event::fake([ShopClosed::class, ShopOpened::class]);

    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $shop = Shop::factory()->create(['owner_id' => $owner->id, 'status' => 'closed']);
    $shop->campaigns()->attach($campaign->id);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('d-m-shop-page')
        ->call('openShop', $shop->id);

    Event::assertDispatched(ShopOpened::class, fn (ShopOpened $event) => $event->shop->id === $shop->id);
    Event::assertNotDispatched(ShopClosed::class);
});

test('editing a shop to draft or hidden does not broadcast an open or closed event', function () {
    Event::fake([ShopClosed::class, ShopOpened::class]);

    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $shop = Shop::factory()->create(['owner_id' => $owner->id, 'status' => 'open']);
    $shop->campaigns()->attach($campaign->id);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('d-m-shop-page')
        ->call('editShop', $shop->id)
        ->set('shopStatus', 'hidden')
        ->call('saveShop');

    Event::assertNotDispatched(ShopOpened::class);
    Event::assertNotDispatched(ShopClosed::class);
});

test('re-saving a shop with the same status does not broadcast again', function () {
    Event::fake([ShopClosed::class, ShopOpened::class]);

    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $shop = Shop::factory()->create(['owner_id' => $owner->id, 'status' => 'open', 'name' => 'Old Name']);
    $shop->campaigns()->attach($campaign->id);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('d-m-shop-page')
        ->call('editShop', $shop->id)
        ->set('shopName', 'New Name')
        ->call('saveShop');

    Event::assertNotDispatched(ShopOpened::class);
    Event::assertNotDispatched(ShopClosed::class);
});

test('creating a new shop as open broadcasts a ShopOpened event', function () {
    Event::fake([ShopClosed::class, ShopOpened::class]);

    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('d-m-shop-page')
        ->call('newShop')
        ->set('shopName', 'The Iron Anvil')
        ->set('shopStatus', 'open')
        ->call('saveShop');

    Event::assertDispatched(ShopOpened::class, fn (ShopOpened $event) => $event->shop->name === 'The Iron Anvil');
});

test('the owner can edit a shop', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $shop = Shop::factory()->create(['owner_id' => $owner->id, 'status' => 'open', 'name' => 'Old Name']);
    $shop->campaigns()->attach($campaign->id);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('d-m-shop-page')
        ->call('editShop', $shop->id)
        ->assertSet('shopName', 'Old Name')
        ->set('shopName', 'The Iron Anvil')
        ->set('shopDescription', 'Fine weapons and armor.')
        ->call('saveShop')
        ->assertHasNoErrors();

    expect($shop->fresh())
        ->name->toBe('The Iron Anvil')
        ->description->toBe('Fine weapons and armor.')
        ->status->toBe('open');
});

test('the owner can create a new shop which starts as a draft', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('d-m-shop-page')
        ->call('newShop')
        ->assertSet('shopName', '')
        ->set('shopName', 'The Iron Anvil')
        ->call('saveShop')
        ->assertHasNoErrors()
        ->assertSet('statusFilter', 'draft');

    $shop = Shop::where('name', 'The Iron Anvil')->firstOrFail();

    expect($shop->status)->toBe('draft')
        ->and($shop->owner_id)->toBe($owner->id)
        ->and($campaign->shops()->where('shops.id', $shop->id)->exists())->toBeTrue();
});

test('the owner can duplicate a shop including its stock', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $shop = Shop::factory()->create(['owner_id' => $owner->id, 'status' => 'open', 'name' => 'The Iron Anvil']);
    $shop->campaigns()->attach($campaign->id);
    ShopStock::factory()->count(3)->create(['shop_id' => $shop->id]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('d-m-shop-page')
        ->call('duplicateShop', $shop->id);

    $copy = Shop::where('name', 'The Iron Anvil (Copy)')->firstOrFail();

    expect($copy->status)->toBe('draft')
        ->and($copy->stock()->count())->toBe(3)
        ->and($campaign->shops()->where('shops.id', $copy->id)->exists())->toBeTrue();
});

test('a non owner member cannot mutate shops', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $member->campaigns()->attach($campaign->id);

    $shop = Shop::factory()->create(['owner_id' => $owner->id, 'status' => 'open']);
    $shop->campaigns()->attach($campaign->id);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($member)->test('d-m-shop-page')->call('closeShop', $shop->id)->assertForbidden();
    Livewire::actingAs($member)->test('d-m-shop-page')->call('editShop', $shop->id)->assertForbidden();
    Livewire::actingAs($member)->test('d-m-shop-page')->call('newShop')->assertForbidden();
    Livewire::actingAs($member)->test('d-m-shop-page')->call('duplicateShop', $shop->id)->assertForbidden();

    expect($shop->fresh()->status)->toBe('open');
});

test('cannot mutate a shop belonging to a different campaign by guessing its id', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $otherCampaign = Campaign::factory()->create();
    $otherShop = Shop::factory()->create(['status' => 'open']);
    $otherShop->campaigns()->attach($otherCampaign->id);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('d-m-shop-page')
        ->call('closeShop', $otherShop->id);

    expect($otherShop->fresh()->status)->toBe('open');
});

test('the hidden and all tabs filter shops correctly', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);

    $hiddenShop = Shop::factory()->create(['owner_id' => $owner->id, 'status' => 'hidden', 'name' => 'The Black Market']);
    $hiddenShop->campaigns()->attach($campaign->id);

    // Deliberately not "The Iron Anvil" - that string also appears in this component's
    // "New shop" placeholder text, which would make assertDontSee() a false negative.
    $openShop = Shop::factory()->create(['owner_id' => $owner->id, 'status' => 'open', 'name' => 'Moonleaf Apothecary']);
    $openShop->campaigns()->attach($campaign->id);

    session(['selected_campaign_id' => $campaign->id]);

    $component = Livewire::actingAs($owner)
        ->test('d-m-shop-page')
        ->assertDontSee('The Black Market')
        ->call('setStatusFilter', 'hidden')
        ->assertSee('The Black Market')
        ->assertDontSee('Moonleaf Apothecary');

    $component->call('setStatusFilter', 'all')
        ->assertSee('The Black Market')
        ->assertSee('Moonleaf Apothecary');
});

test('the owner can set a shop to hidden via the edit modal', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $shop = Shop::factory()->create(['owner_id' => $owner->id, 'status' => 'open']);
    $shop->campaigns()->attach($campaign->id);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('d-m-shop-page')
        ->call('editShop', $shop->id)
        ->set('shopStatus', 'hidden')
        ->call('saveShop')
        ->assertHasNoErrors();

    expect($shop->fresh()->status)->toBe('hidden');
});

test('the owner can view stock and available items when managing a shop', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $shop = Shop::factory()->create(['owner_id' => $owner->id, 'status' => 'open']);
    $shop->campaigns()->attach($campaign->id);

    $stockedItem = Item::factory()->create(['name' => 'Longsword']);
    ShopStock::factory()->create(['shop_id' => $shop->id, 'item_id' => $stockedItem->id, 'price' => 15, 'quantity' => 2]);

    $availableItem = Item::factory()->create(['name' => 'Shield']);

    session(['selected_campaign_id' => $campaign->id]);

    $component = Livewire::actingAs($owner)
        ->test('d-m-shop-page')
        ->call('manageStock', $shop->id);

    $component->assertSee('Longsword')
        ->assertSee('Qty 2');

    expect($component->get('availableItems')->pluck('id')->all())->toBe([$availableItem->id]);
});

test('the owner can add an item to a shop and the price prefills from the item default', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $shop = Shop::factory()->create(['owner_id' => $owner->id, 'status' => 'open']);
    $shop->campaigns()->attach($campaign->id);
    $item = Item::factory()->create(['name' => 'Potion of Healing', 'default_price' => 50]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('d-m-shop-page')
        ->call('manageStock', $shop->id)
        ->set('newStockItemId', (string) $item->id)
        ->assertSet('newStockPrice', 50)
        ->set('newStockQuantity', 5)
        ->call('addStockItem')
        ->assertHasNoErrors();

    $stock = ShopStock::where('shop_id', $shop->id)->where('item_id', $item->id)->firstOrFail();

    expect($stock->price)->toBe(50)
        ->and($stock->quantity)->toBe(5);
});

test('the owner can remove an item from a shop', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $shop = Shop::factory()->create(['owner_id' => $owner->id, 'status' => 'open']);
    $shop->campaigns()->attach($campaign->id);
    $stock = ShopStock::factory()->create(['shop_id' => $shop->id]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('d-m-shop-page')
        ->call('manageStock', $shop->id)
        ->call('removeStockItem', $stock->id);

    expect(ShopStock::find($stock->id))->toBeNull();
});

test('cannot add an item that is not in the available items list', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $shop = Shop::factory()->create(['owner_id' => $owner->id, 'status' => 'open']);
    $shop->campaigns()->attach($campaign->id);
    $item = Item::factory()->create();
    ShopStock::factory()->create(['shop_id' => $shop->id, 'item_id' => $item->id]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('d-m-shop-page')
        ->call('manageStock', $shop->id)
        ->set('newStockItemId', (string) $item->id)
        ->call('addStockItem')
        ->assertHasErrors(['newStockItemId']);

    expect(ShopStock::where('shop_id', $shop->id)->count())->toBe(1);
});

test('a non owner cannot manage shop items', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $member->campaigns()->attach($campaign->id);
    $shop = Shop::factory()->create(['owner_id' => $owner->id, 'status' => 'open']);
    $shop->campaigns()->attach($campaign->id);
    $stock = ShopStock::factory()->create(['shop_id' => $shop->id]);
    $item = Item::factory()->create();

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($member)->test('d-m-shop-page')->call('manageStock', $shop->id)->assertForbidden();

    Livewire::actingAs($member)
        ->test('d-m-shop-page')
        ->set('managingStockShopId', $shop->id)
        ->set('newStockItemId', (string) $item->id)
        ->call('addStockItem')
        ->assertForbidden();

    Livewire::actingAs($member)
        ->test('d-m-shop-page')
        ->set('managingStockShopId', $shop->id)
        ->call('removeStockItem', $stock->id)
        ->assertForbidden();

    expect(ShopStock::where('shop_id', $shop->id)->count())->toBe(1);
});

test('cannot manage stock for a shop belonging to a different campaign by guessing its id', function () {
    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $otherCampaign = Campaign::factory()->create();
    $otherShop = Shop::factory()->create(['status' => 'open']);
    $otherShop->campaigns()->attach($otherCampaign->id);
    $otherStock = ShopStock::factory()->create(['shop_id' => $otherShop->id]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('d-m-shop-page')
        ->set('managingStockShopId', $otherShop->id)
        ->call('removeStockItem', $otherStock->id);

    expect(ShopStock::find($otherStock->id))->not->toBeNull();
});

test('resets the filter and refreshes the list when the campaign-switched event fires', function () {
    $owner = User::factory()->create();
    $firstCampaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $secondCampaign = Campaign::factory()->create(['owner_id' => $owner->id]);

    $secondCampaignShop = Shop::factory()->create(['owner_id' => $owner->id, 'status' => 'open', 'name' => 'Highmoor Market']);
    $secondCampaignShop->campaigns()->attach($secondCampaign->id);

    session(['selected_campaign_id' => $firstCampaign->id]);

    Livewire::actingAs($owner)
        ->test('d-m-shop-page')
        ->call('setStatusFilter', 'draft')
        ->dispatch('campaign-switched', campaignId: $secondCampaign->id)
        ->assertSet('statusFilter', 'open')
        ->assertSee('Highmoor Market');
});
