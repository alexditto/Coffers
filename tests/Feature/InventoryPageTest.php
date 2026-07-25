<?php

use App\Models\Campaign;
use App\Models\Character;
use App\Models\Inventory;
use App\Models\Item;
use App\Models\ItemCount;
use App\Models\User;
use Livewire\Livewire;

test('shows an empty state when no campaign is selected', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('inventory-page')
        ->assertSee('Select or create a campaign');
});

test('shows an empty state for a campaign the user does not have access to', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('inventory-page')
        ->assertSee('Select or create a campaign');
});

test('shows an empty state when the user has no character in the selected campaign', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('inventory-page')
        ->assertSee('character in this campaign yet');
});

test('shows the gold banner and the items in the characters inventory', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);
    $character = Character::factory()->create(['user_id' => $user->id, 'campaign_id' => $campaign->id]);
    $inventory = Inventory::factory()->create(['character_id' => $character->id, 'gold' => 240]);
    $character->update(['inventory_id' => $inventory->id]);
    $sword = Item::factory()->create(['name' => 'Longsword']);
    ItemCount::factory()->create(['inventory_id' => $inventory->id, 'item_id' => $sword->id, 'count' => 2]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('inventory-page')
        ->assertSee('240 gp')
        ->assertSee('Longsword')
        ->assertSee('×2');
});

test('items with a zero count are not shown', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);
    $character = Character::factory()->create(['user_id' => $user->id, 'campaign_id' => $campaign->id]);
    $inventory = Inventory::factory()->create(['character_id' => $character->id]);
    $character->update(['inventory_id' => $inventory->id]);
    $item = Item::factory()->create(['name' => 'Empty Flask']);
    ItemCount::factory()->create(['inventory_id' => $inventory->id, 'item_id' => $item->id, 'count' => 0]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('inventory-page')
        ->assertSee('Your inventory is empty.')
        ->assertDontSee('Empty Flask');
});

test('search filters the inventory by item name', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);
    $character = Character::factory()->create(['user_id' => $user->id, 'campaign_id' => $campaign->id]);
    $inventory = Inventory::factory()->create(['character_id' => $character->id]);
    $character->update(['inventory_id' => $inventory->id]);
    $sword = Item::factory()->create(['name' => 'Longsword']);
    $potion = Item::factory()->create(['name' => 'Potion of Healing']);
    ItemCount::factory()->create(['inventory_id' => $inventory->id, 'item_id' => $sword->id, 'count' => 1]);
    ItemCount::factory()->create(['inventory_id' => $inventory->id, 'item_id' => $potion->id, 'count' => 3]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('inventory-page')
        ->assertSee('Longsword')
        ->assertSee('Potion of Healing')
        ->set('search', 'potion')
        ->assertDontSee('Longsword')
        ->assertSee('Potion of Healing');
});

test('shows a no results message when the search does not match anything', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);
    $character = Character::factory()->create(['user_id' => $user->id, 'campaign_id' => $campaign->id]);
    $inventory = Inventory::factory()->create(['character_id' => $character->id]);
    $character->update(['inventory_id' => $inventory->id]);
    $sword = Item::factory()->create(['name' => 'Longsword']);
    ItemCount::factory()->create(['inventory_id' => $inventory->id, 'item_id' => $sword->id, 'count' => 1]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('inventory-page')
        ->set('search', 'nonexistent item')
        ->assertSee('No items match')
        ->assertSee('nonexistent item');
});

test('the owner can remove an item after confirming', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);
    $character = Character::factory()->create(['user_id' => $user->id, 'campaign_id' => $campaign->id]);
    $inventory = Inventory::factory()->create(['character_id' => $character->id]);
    $character->update(['inventory_id' => $inventory->id]);
    $sword = Item::factory()->create(['name' => 'Longsword']);
    $itemCount = ItemCount::factory()->create(['inventory_id' => $inventory->id, 'item_id' => $sword->id, 'count' => 1]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('inventory-page')
        ->call('confirmRemove', $itemCount->id)
        ->assertSee('Remove item?')
        ->assertSee('Longsword')
        ->call('removeItem');

    expect(ItemCount::find($itemCount->id))->toBeNull();
});

test('cannot remove another characters item by guessing its id', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);
    $character = Character::factory()->create(['user_id' => $user->id, 'campaign_id' => $campaign->id]);
    $inventory = Inventory::factory()->create(['character_id' => $character->id]);
    $character->update(['inventory_id' => $inventory->id]);

    $otherItemCount = ItemCount::factory()->create();

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('inventory-page')
        ->call('confirmRemove', $otherItemCount->id)
        ->call('removeItem');

    expect(ItemCount::find($otherItemCount->id))->not->toBeNull();
});

test('the give button is hidden when there are no other party members', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);
    $character = Character::factory()->create(['user_id' => $user->id, 'campaign_id' => $campaign->id]);
    $inventory = Inventory::factory()->create(['character_id' => $character->id]);
    $character->update(['inventory_id' => $inventory->id]);
    $sword = Item::factory()->create(['name' => 'Longsword']);
    ItemCount::factory()->create(['inventory_id' => $inventory->id, 'item_id' => $sword->id, 'count' => 1]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('inventory-page')
        ->assertDontSeeHtml('wire:click="openGive');
});

test('the owner can give an item to another party member', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);
    $character = Character::factory()->create(['user_id' => $user->id, 'campaign_id' => $campaign->id]);
    $inventory = Inventory::factory()->create(['character_id' => $character->id]);
    $character->update(['inventory_id' => $inventory->id]);
    $sword = Item::factory()->create(['name' => 'Longsword']);
    $itemCount = ItemCount::factory()->create(['inventory_id' => $inventory->id, 'item_id' => $sword->id, 'count' => 1]);

    $ally = Character::factory()->create(['campaign_id' => $campaign->id, 'name' => 'Mirabel']);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('inventory-page')
        ->call('openGive', $itemCount->id)
        ->assertSee('Mirabel')
        ->set('giveToCharacterId', (string) $ally->id)
        ->call('giveItem')
        ->assertHasNoErrors();

    expect(ItemCount::find($itemCount->id))->toBeNull();

    $ally->refresh();
    $allyItemCount = $ally->inventory->item_counts()->where('item_id', $sword->id)->first();

    expect($allyItemCount)->not->toBeNull()
        ->and($allyItemCount->count)->toBe(1);
});

test('giving an item creates an inventory for the recipient if one does not exist', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);
    $character = Character::factory()->create(['user_id' => $user->id, 'campaign_id' => $campaign->id]);
    $inventory = Inventory::factory()->create(['character_id' => $character->id]);
    $character->update(['inventory_id' => $inventory->id]);
    $sword = Item::factory()->create();
    $itemCount = ItemCount::factory()->create(['inventory_id' => $inventory->id, 'item_id' => $sword->id, 'count' => 1]);

    $ally = Character::factory()->create(['campaign_id' => $campaign->id, 'inventory_id' => null]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('inventory-page')
        ->call('openGive', $itemCount->id)
        ->set('giveToCharacterId', (string) $ally->id)
        ->call('giveItem');

    expect($ally->fresh()->inventory)->not->toBeNull();
});

test('giving an item accumulates onto the recipients existing count for that item', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);
    $character = Character::factory()->create(['user_id' => $user->id, 'campaign_id' => $campaign->id]);
    $inventory = Inventory::factory()->create(['character_id' => $character->id]);
    $character->update(['inventory_id' => $inventory->id]);
    $sword = Item::factory()->create();
    $itemCount = ItemCount::factory()->create(['inventory_id' => $inventory->id, 'item_id' => $sword->id, 'count' => 3]);

    $ally = Character::factory()->create(['campaign_id' => $campaign->id]);
    $allyInventory = Inventory::factory()->create(['character_id' => $ally->id]);
    $ally->update(['inventory_id' => $allyInventory->id]);
    ItemCount::factory()->create(['inventory_id' => $allyInventory->id, 'item_id' => $sword->id, 'count' => 2]);

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('inventory-page')
        ->call('openGive', $itemCount->id)
        ->set('giveToCharacterId', (string) $ally->id)
        ->call('giveItem');

    $allyItemCount = $allyInventory->item_counts()->where('item_id', $sword->id)->first();

    expect($allyItemCount->count)->toBe(5);
});

test('cannot give an item to a character outside the campaign by guessing its id', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $user->campaigns()->attach($campaign->id);
    $character = Character::factory()->create(['user_id' => $user->id, 'campaign_id' => $campaign->id]);
    $inventory = Inventory::factory()->create(['character_id' => $character->id]);
    $character->update(['inventory_id' => $inventory->id]);
    $sword = Item::factory()->create();
    $itemCount = ItemCount::factory()->create(['inventory_id' => $inventory->id, 'item_id' => $sword->id, 'count' => 1]);

    // Give the campaign one real other member so the give UI is reachable,
    // then attempt to give to a character from an entirely different campaign.
    Character::factory()->create(['campaign_id' => $campaign->id]);
    $stranger = Character::factory()->create();

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('inventory-page')
        ->call('openGive', $itemCount->id)
        ->set('giveToCharacterId', (string) $stranger->id)
        ->call('giveItem')
        ->assertHasErrors(['giveToCharacterId']);

    expect(ItemCount::find($itemCount->id))->not->toBeNull();
});

test('refreshes the inventory when the campaign-switched event fires', function () {
    $user = User::factory()->create();
    $firstCampaign = Campaign::factory()->create();
    $secondCampaign = Campaign::factory()->create();
    $user->campaigns()->attach([$firstCampaign->id, $secondCampaign->id]);

    $firstCharacter = Character::factory()->create(['user_id' => $user->id, 'campaign_id' => $firstCampaign->id]);
    $firstInventory = Inventory::factory()->create(['character_id' => $firstCharacter->id, 'gold' => 50]);
    $firstCharacter->update(['inventory_id' => $firstInventory->id]);
    $sword = Item::factory()->create(['name' => 'Longsword']);
    ItemCount::factory()->create(['inventory_id' => $firstInventory->id, 'item_id' => $sword->id, 'count' => 1]);

    $secondCharacter = Character::factory()->create(['user_id' => $user->id, 'campaign_id' => $secondCampaign->id]);
    $secondInventory = Inventory::factory()->create(['character_id' => $secondCharacter->id, 'gold' => 99]);
    $secondCharacter->update(['inventory_id' => $secondInventory->id]);
    $shield = Item::factory()->create(['name' => 'Shield']);
    ItemCount::factory()->create(['inventory_id' => $secondInventory->id, 'item_id' => $shield->id, 'count' => 1]);

    session(['selected_campaign_id' => $firstCampaign->id]);

    $component = Livewire::actingAs($user)
        ->test('inventory-page')
        ->assertSee('50 gp')
        ->assertSee('Longsword')
        ->assertDontSee('Shield');

    // In production, campaign-selector-banner updates the session before dispatching
    // this event; simulate that here since we're dispatching it directly in the test.
    session(['selected_campaign_id' => $secondCampaign->id]);

    $component->dispatch('campaign-switched', campaignId: $secondCampaign->id)
        ->assertSee('99 gp')
        ->assertSee('Shield')
        ->assertDontSee('Longsword');
});
