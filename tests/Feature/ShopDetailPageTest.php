<?php

use App\Models\Campaign;
use App\Models\Character;
use App\Models\Inventory;
use App\Models\Item;
use App\Models\Shop;
use App\Models\ShoppingCart;
use App\Models\ShopStock;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected to the login page', function () {
    $shop = Shop::factory()->create();

    $this->get(route('shop-detail', ['shop' => $shop->id]))->assertRedirect(route('login'));
});

test('shows the shop banner and its stock when it is open in the users selected campaign', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);
    $shop = Shop::factory()->create(['status' => 'open', 'name' => 'The Iron Anvil']);
    $shop->campaigns()->attach($campaign->id);
    $item = Item::factory()->create(['name' => 'Longsword']);
    ShopStock::factory()->create(['shop_id' => $shop->id, 'item_id' => $item->id, 'price' => 15, 'quantity' => 3]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('shop-detail-page', ['shopId' => $shop->id])
        ->assertSee('The Iron Anvil')
        ->assertSee('1 item')
        ->assertSee('Longsword')
        ->assertSee('15 gp')
        ->assertSee('3 left');
});

test('shows an empty stock message when the shop has no items', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);
    $shop = Shop::factory()->create(['status' => 'open']);
    $shop->campaigns()->attach($campaign->id);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('shop-detail-page', ['shopId' => $shop->id])
        ->assertSee('This shop has no items in stock yet.');
});

test('a closed shop is not available even if it belongs to the selected campaign', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);
    $shop = Shop::factory()->create(['status' => 'closed', 'name' => 'Salty Mermaid Tavern']);
    $shop->campaigns()->attach($campaign->id);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('shop-detail-page', ['shopId' => $shop->id])
        ->assertSee('available right now')
        ->assertDontSee('Salty Mermaid Tavern');
});

test('a shop belonging to a different campaign than the one selected is not available', function () {
    $user = User::factory()->create();
    $selectedCampaign = Campaign::factory()->create();
    $otherCampaign = Campaign::factory()->create();
    $user->campaigns()->attach([$selectedCampaign->id, $otherCampaign->id]);

    $shop = Shop::factory()->create(['status' => 'open', 'name' => 'The Iron Anvil']);
    $shop->campaigns()->attach($otherCampaign->id);

    session(['selected_campaign_id' => $selectedCampaign->id]);

    Livewire::actingAs($user)
        ->test('shop-detail-page', ['shopId' => $shop->id])
        ->assertSee('available right now')
        ->assertDontSee('The Iron Anvil');
});

test('a shop in a campaign the user has no access to at all is not available', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $shop = Shop::factory()->create(['status' => 'open', 'name' => 'The Iron Anvil']);
    $shop->campaigns()->attach($campaign->id);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('shop-detail-page', ['shopId' => $shop->id])
        ->assertSee('available right now')
        ->assertDontSee('The Iron Anvil');
});

test('authenticated users can visit the shop detail route even for a non-existent shop', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('shop-detail', ['shop' => 999999]))->assertOk();
});

test('shows a notice and no add button when the user has no character in the campaign', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);
    $shop = Shop::factory()->create(['status' => 'open']);
    $shop->campaigns()->attach($campaign->id);
    ShopStock::factory()->create(['shop_id' => $shop->id, 'quantity' => 5]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('shop-detail-page', ['shopId' => $shop->id])
        ->assertSee('You need a character in this campaign to shop here.')
        ->assertDontSeeHtml('wire:click="addToCart');
});

test('adding an item to the cart claims it: decrements shop stock and increases the cart count and total', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);
    $character = Character::factory()->create(['user_id' => $user->id, 'campaign_id' => $campaign->id]);
    $shop = Shop::factory()->create(['status' => 'open']);
    $shop->campaigns()->attach($campaign->id);
    $stock = ShopStock::factory()->create(['shop_id' => $shop->id, 'price' => 15, 'quantity' => 3]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('shop-detail-page', ['shopId' => $shop->id])
        ->call('addToCart', $stock->id)
        ->assertSee('Cart · 1 item')
        ->assertSee('Checkout · 15 gp');

    expect($stock->fresh()->quantity)->toBe(2)
        ->and(ShoppingCart::where('shop_stock_id', $stock->id)->where('is_purchased', false)->exists())->toBeTrue();

    $character->refresh();
    expect($character->inventory)->not->toBeNull();
});

test('adding to cart creates an inventory for the character if one does not exist yet', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);
    $character = Character::factory()->create(['user_id' => $user->id, 'campaign_id' => $campaign->id, 'inventory_id' => null]);
    $shop = Shop::factory()->create(['status' => 'open']);
    $shop->campaigns()->attach($campaign->id);
    $stock = ShopStock::factory()->create(['shop_id' => $shop->id, 'quantity' => 3]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('shop-detail-page', ['shopId' => $shop->id])
        ->call('addToCart', $stock->id);

    expect($character->fresh()->inventory)->not->toBeNull();
});

test('adding multiple items sums the cart total correctly', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);
    Character::factory()->create(['user_id' => $user->id, 'campaign_id' => $campaign->id]);
    $shop = Shop::factory()->create(['status' => 'open']);
    $shop->campaigns()->attach($campaign->id);
    $sword = ShopStock::factory()->create(['shop_id' => $shop->id, 'price' => 15, 'quantity' => 5]);
    $shield = ShopStock::factory()->create(['shop_id' => $shop->id, 'price' => 10, 'quantity' => 5]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('shop-detail-page', ['shopId' => $shop->id])
        ->call('addToCart', $sword->id)
        ->call('addToCart', $shield->id)
        ->assertSee('Cart · 2 items')
        ->assertSee('Checkout · 25 gp');
});

test('cannot add a sold out item to the cart', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);
    Character::factory()->create(['user_id' => $user->id, 'campaign_id' => $campaign->id]);
    $shop = Shop::factory()->create(['status' => 'open']);
    $shop->campaigns()->attach($campaign->id);
    $stock = ShopStock::factory()->create(['shop_id' => $shop->id, 'quantity' => 0]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('shop-detail-page', ['shopId' => $shop->id])
        ->assertSee('Sold out')
        ->call('addToCart', $stock->id)
        ->assertSee('Cart · 0 items');

    expect(ShoppingCart::where('shop_stock_id', $stock->id)->exists())->toBeFalse();
});

test('cannot add stock belonging to a different shop by guessing its id', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);
    Character::factory()->create(['user_id' => $user->id, 'campaign_id' => $campaign->id]);
    $shop = Shop::factory()->create(['status' => 'open']);
    $shop->campaigns()->attach($campaign->id);

    $otherShop = Shop::factory()->create(['status' => 'open']);
    $otherStock = ShopStock::factory()->create(['shop_id' => $otherShop->id, 'quantity' => 5]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('shop-detail-page', ['shopId' => $shop->id])
        ->call('addToCart', $otherStock->id);

    expect($otherStock->fresh()->quantity)->toBe(5)
        ->and(ShoppingCart::where('shop_stock_id', $otherStock->id)->exists())->toBeFalse();
});

test('checkout opens the cart review without completing a purchase', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);
    Character::factory()->create(['user_id' => $user->id, 'campaign_id' => $campaign->id]);
    $shop = Shop::factory()->create(['status' => 'open']);
    $shop->campaigns()->attach($campaign->id);
    $stock = ShopStock::factory()->create(['shop_id' => $shop->id, 'quantity' => 3]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('shop-detail-page', ['shopId' => $shop->id])
        ->call('addToCart', $stock->id)
        ->call('checkout')
        ->assertSee('Total')
        ->assertDontSee('Your cart is empty.');

    expect(ShoppingCart::where('shop_stock_id', $stock->id)->where('is_purchased', false)->exists())->toBeTrue();
});

test('creating a shopping cart entry decrements the shop stock quantity and deleting it restores the quantity', function () {
    $stock = ShopStock::factory()->create(['quantity' => 5]);
    $inventory = Inventory::factory()->create();

    $cart = ShoppingCart::create([
        'shop_stock_id' => $stock->id,
        'inventory_id' => $inventory->id,
        'is_purchased' => false,
    ]);

    expect($stock->fresh()->quantity)->toBe(4);

    $cart->delete();

    expect($stock->fresh()->quantity)->toBe(5);
});

test('the cart review sheet shows grouped quantities, total, and gold before/after', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);
    $character = Character::factory()->create(['user_id' => $user->id, 'campaign_id' => $campaign->id]);
    $inventory = Inventory::factory()->create(['character_id' => $character->id, 'gold' => 240]);
    $character->update(['inventory_id' => $inventory->id]);
    $shop = Shop::factory()->create(['status' => 'open']);
    $shop->campaigns()->attach($campaign->id);
    $sword = ShopStock::factory()->create(['shop_id' => $shop->id, 'price' => 15, 'quantity' => 5]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('shop-detail-page', ['shopId' => $shop->id])
        ->call('addToCart', $sword->id)
        ->call('addToCart', $sword->id)
        ->call('checkout')
        ->assertSet('cartLines.0.quantity', 2)
        ->assertSee('30 gp')
        ->assertSee('240 gp')
        ->assertSee('210 gp');
});

test('insufficient gold disables buying and blocks moving to confirmation', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);
    $character = Character::factory()->create(['user_id' => $user->id, 'campaign_id' => $campaign->id]);
    $inventory = Inventory::factory()->create(['character_id' => $character->id, 'gold' => 10]);
    $character->update(['inventory_id' => $inventory->id]);
    $shop = Shop::factory()->create(['status' => 'open']);
    $shop->campaigns()->attach($campaign->id);
    $stock = ShopStock::factory()->create(['shop_id' => $shop->id, 'price' => 15, 'quantity' => 5]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('shop-detail-page', ['shopId' => $shop->id])
        ->call('addToCart', $stock->id)
        ->assertSet('canAfford', false)
        ->assertSet('shortBy', 5)
        ->call('openConfirm');

    // Confirming without enough gold must not touch gold, stock, or item counts.
    expect($inventory->fresh()->gold)->toBe(10)
        ->and(ShoppingCart::where('shop_stock_id', $stock->id)->where('is_purchased', false)->exists())->toBeTrue();
});

test('removing an item from the cart restores the shop stock quantity', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);
    Character::factory()->create(['user_id' => $user->id, 'campaign_id' => $campaign->id]);
    $shop = Shop::factory()->create(['status' => 'open']);
    $shop->campaigns()->attach($campaign->id);
    $stock = ShopStock::factory()->create(['shop_id' => $shop->id, 'quantity' => 5]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('shop-detail-page', ['shopId' => $shop->id])
        ->call('addToCart', $stock->id)
        ->call('addToCart', $stock->id)
        ->call('removeFromCart', $stock->id)
        ->assertSee('Cart · 1 item');

    expect($stock->fresh()->quantity)->toBe(4);
});

test('confirming a purchase deducts gold, credits inventory, marks the cart purchased, and shows a receipt', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);
    $character = Character::factory()->create(['user_id' => $user->id, 'campaign_id' => $campaign->id]);
    $inventory = Inventory::factory()->create(['character_id' => $character->id, 'gold' => 240]);
    $character->update(['inventory_id' => $inventory->id]);
    $shop = Shop::factory()->create(['status' => 'open']);
    $shop->campaigns()->attach($campaign->id);
    $sword = ShopStock::factory()->create(['shop_id' => $shop->id, 'price' => 15, 'quantity' => 5]);
    $dagger = ShopStock::factory()->create(['shop_id' => $shop->id, 'price' => 2, 'quantity' => 5]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('shop-detail-page', ['shopId' => $shop->id])
        ->call('addToCart', $sword->id)
        ->call('addToCart', $dagger->id)
        ->call('openConfirm')
        ->call('confirmPurchase')
        ->assertSet('receiptTotal', 17)
        ->assertSet('receiptBalanceAfter', 223)
        ->assertSee('Purchase complete')
        ->assertSee('223 gp');

    expect($inventory->fresh()->gold)->toBe(223)
        ->and(ShoppingCart::where('inventory_id', $inventory->id)->where('is_purchased', false)->exists())->toBeFalse()
        ->and(ShoppingCart::where('inventory_id', $inventory->id)->where('is_purchased', true)->count())->toBe(2);

    $swordCount = $inventory->item_counts()->where('item_id', $sword->item_id)->first();
    $daggerCount = $inventory->item_counts()->where('item_id', $dagger->item_id)->first();

    expect($swordCount->count)->toBe(1)
        ->and($daggerCount->count)->toBe(1);

    // The purchased items stay claimed - stock does not get restored on purchase.
    expect($sword->fresh()->quantity)->toBe(4)
        ->and($dagger->fresh()->quantity)->toBe(4);
});

test('buying multiple units of the same item accumulates the item count', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);
    $character = Character::factory()->create(['user_id' => $user->id, 'campaign_id' => $campaign->id]);
    $inventory = Inventory::factory()->create(['character_id' => $character->id, 'gold' => 100]);
    $character->update(['inventory_id' => $inventory->id]);
    $shop = Shop::factory()->create(['status' => 'open']);
    $shop->campaigns()->attach($campaign->id);
    $stock = ShopStock::factory()->create(['shop_id' => $shop->id, 'price' => 5, 'quantity' => 10]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('shop-detail-page', ['shopId' => $shop->id])
        ->call('addToCart', $stock->id)
        ->call('addToCart', $stock->id)
        ->call('addToCart', $stock->id)
        ->call('openConfirm')
        ->call('confirmPurchase');

    $itemCount = $inventory->item_counts()->where('item_id', $stock->item_id)->first();

    expect($itemCount->count)->toBe(3)
        ->and($inventory->fresh()->gold)->toBe(85);
});

test('cannot confirm a purchase with an empty cart', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);
    $character = Character::factory()->create(['user_id' => $user->id, 'campaign_id' => $campaign->id]);
    $inventory = Inventory::factory()->create(['character_id' => $character->id, 'gold' => 100]);
    $character->update(['inventory_id' => $inventory->id]);
    $shop = Shop::factory()->create(['status' => 'open']);
    $shop->campaigns()->attach($campaign->id);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('shop-detail-page', ['shopId' => $shop->id])
        ->call('confirmPurchase');

    expect($inventory->fresh()->gold)->toBe(100);
});

test('backToCart returns from the confirmation step to the cart review', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);
    $character = Character::factory()->create(['user_id' => $user->id, 'campaign_id' => $campaign->id]);
    $inventory = Inventory::factory()->create(['character_id' => $character->id, 'gold' => 100]);
    $character->update(['inventory_id' => $inventory->id]);
    $shop = Shop::factory()->create(['status' => 'open']);
    $shop->campaigns()->attach($campaign->id);
    $stock = ShopStock::factory()->create(['shop_id' => $shop->id, 'price' => 5, 'quantity' => 5]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('shop-detail-page', ['shopId' => $shop->id])
        ->call('addToCart', $stock->id)
        ->call('openConfirm')
        ->call('backToCart')
        ->call('confirmPurchase')
        ->assertSee('Purchase complete');

    expect(ShoppingCart::where('shop_stock_id', $stock->id)->where('is_purchased', true)->exists())->toBeTrue();
});

test('registers echo listeners scoped to this shop for open and close broadcasts', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);
    $shop = Shop::factory()->create(['status' => 'open']);
    $shop->campaigns()->attach($campaign->id);

    session(['selected_campaign_id' => $campaign->id]);

    $listeners = Livewire::actingAs($user)
        ->test('shop-detail-page', ['shopId' => $shop->id])
        ->instance()
        ->getListeners();

    expect($listeners)->toHaveKey("echo-private:shop.{$shop->id},ShopOpened")
        ->and($listeners)->toHaveKey("echo-private:shop.{$shop->id},ShopClosed");
});

test('a shop closed while the confirmation modal is open cannot be purchased', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);
    $character = Character::factory()->create(['user_id' => $user->id, 'campaign_id' => $campaign->id]);
    $inventory = Inventory::factory()->create(['character_id' => $character->id, 'gold' => 100]);
    $character->update(['inventory_id' => $inventory->id]);
    $shop = Shop::factory()->create(['status' => 'open']);
    $shop->campaigns()->attach($campaign->id);
    $stock = ShopStock::factory()->create(['shop_id' => $shop->id, 'price' => 10, 'quantity' => 5]);

    session(['selected_campaign_id' => $campaign->id]);

    $component = Livewire::actingAs($user)
        ->test('shop-detail-page', ['shopId' => $shop->id])
        ->call('addToCart', $stock->id)
        ->call('openConfirm');

    // The DM closes the shop out from under the player after the cart is already reviewed.
    $shop->update(['status' => 'closed']);

    $component->call('confirmPurchase');

    expect($inventory->fresh()->gold)->toBe(100)
        ->and(ShoppingCart::where('shop_stock_id', $stock->id)->where('is_purchased', true)->exists())->toBeFalse();
});

test('receiving a shop closed broadcast refreshes the page to show the shop is unavailable', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);
    $shop = Shop::factory()->create(['status' => 'open', 'name' => 'The Iron Anvil']);
    $shop->campaigns()->attach($campaign->id);

    session(['selected_campaign_id' => $campaign->id]);

    $component = Livewire::actingAs($user)
        ->test('shop-detail-page', ['shopId' => $shop->id])
        ->assertSee('The Iron Anvil');

    $shop->update(['status' => 'closed']);

    $component->call('onShopStatusChanged')
        ->assertSee("This shop isn't available right now.", false);
});
