<?php

use App\Filament\Resources\Shops\Pages\EditShop;
use App\Filament\Resources\Shops\RelationManagers\CampaignsRelationManager;
use App\Filament\Resources\Shops\RelationManagers\ItemsRelationManager;
use App\Models\Campaign;
use App\Models\Item;
use App\Models\Shop;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

test('the items and campaigns relation managers are registered on the shop edit page', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $shop = Shop::factory()->create();

    Livewire::actingAs($admin)
        ->test(EditShop::class, ['record' => $shop->id])
        ->assertOk()
        ->assertSeeLivewire(ItemsRelationManager::class)
        ->assertSee('Campaigns');
});

test('the items relation manager lists the shops stock with price and quantity', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $shop = Shop::factory()->create();
    $item = Item::factory()->create(['name' => 'Longsword']);
    $shop->items()->attach($item->id, ['price' => 15, 'quantity' => 3]);

    Livewire::actingAs($admin)
        ->test(ItemsRelationManager::class, [
            'ownerRecord' => $shop,
            'pageClass' => EditShop::class,
        ])
        ->assertOk()
        ->assertCanSeeTableRecords([$item])
        ->assertSee('Longsword');
});

test('an item can be attached to a shop with a price and quantity', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $shop = Shop::factory()->create();
    $item = Item::factory()->create();

    Livewire::actingAs($admin)
        ->test(ItemsRelationManager::class, [
            'ownerRecord' => $shop,
            'pageClass' => EditShop::class,
        ])
        ->callAction(TestAction::make('attach')->table(), [
            'recordId' => $item->id,
            'price' => 42,
            'quantity' => 7,
        ])
        ->assertCanSeeTableRecords([$item]);

    $stock = $shop->items()->where('items.id', $item->id)->first();

    expect($stock->pivot->price)->toBe(42)
        ->and($stock->pivot->quantity)->toBe(7);
});

test('an item can be detached from a shop', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $shop = Shop::factory()->create();
    $item = Item::factory()->create();
    $shop->items()->attach($item->id, ['price' => 10, 'quantity' => 1]);

    Livewire::actingAs($admin)
        ->test(ItemsRelationManager::class, [
            'ownerRecord' => $shop,
            'pageClass' => EditShop::class,
        ])
        ->callAction(TestAction::make('detach')->table($item))
        ->assertCanNotSeeTableRecords([$item]);

    expect($shop->items()->where('items.id', $item->id)->exists())->toBeFalse();
});

test('the campaigns relation manager lists the shops campaigns', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $shop = Shop::factory()->create();
    $campaign = Campaign::factory()->create();
    $shop->campaigns()->attach($campaign->id);

    Livewire::actingAs($admin)
        ->test(CampaignsRelationManager::class, [
            'ownerRecord' => $shop,
            'pageClass' => EditShop::class,
        ])
        ->assertOk()
        ->assertCanSeeTableRecords([$campaign]);
});
