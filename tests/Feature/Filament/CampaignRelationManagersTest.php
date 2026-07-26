<?php

use App\Filament\Resources\Campaigns\Pages\EditCampaign;
use App\Filament\Resources\Campaigns\RelationManagers\PlayersRelationManager;
use App\Filament\Resources\Campaigns\RelationManagers\ShopsRelationManager;
use App\Models\Campaign;
use App\Models\Shop;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

test('the players and shops relation managers are registered on the campaign edit page', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $campaign = Campaign::factory()->create();

    Livewire::actingAs($admin)
        ->test(EditCampaign::class, ['record' => $campaign->id])
        ->assertOk()
        ->assertSeeLivewire(PlayersRelationManager::class)
        ->assertSee('Shops');
});

test('the players relation manager lists the campaigns players', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $campaign = Campaign::factory()->create();
    $player = User::factory()->create();
    $campaign->players()->attach($player->id);

    Livewire::actingAs($admin)
        ->test(PlayersRelationManager::class, [
            'ownerRecord' => $campaign,
            'pageClass' => EditCampaign::class,
        ])
        ->assertOk()
        ->assertCanSeeTableRecords([$player]);
});

test('a player can be attached to and detached from a campaign', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $campaign = Campaign::factory()->create();
    $player = User::factory()->create();

    Livewire::actingAs($admin)
        ->test(PlayersRelationManager::class, [
            'ownerRecord' => $campaign,
            'pageClass' => EditCampaign::class,
        ])
        ->callAction(TestAction::make('attach')->table(), [
            'recordId' => $player->id,
        ])
        ->assertCanSeeTableRecords([$player]);

    expect($campaign->players()->pluck('users.id'))->toContain($player->id);

    Livewire::actingAs($admin)
        ->test(PlayersRelationManager::class, [
            'ownerRecord' => $campaign,
            'pageClass' => EditCampaign::class,
        ])
        ->callAction(TestAction::make('detach')->table($player))
        ->assertCanNotSeeTableRecords([$player]);

    expect($campaign->players()->pluck('users.id'))->not->toContain($player->id);
});

test('the shops relation manager lists the campaigns shops', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $campaign = Campaign::factory()->create();
    $shop = Shop::factory()->create();
    $campaign->shops()->attach($shop->id);

    Livewire::actingAs($admin)
        ->test(ShopsRelationManager::class, [
            'ownerRecord' => $campaign,
            'pageClass' => EditCampaign::class,
        ])
        ->assertOk()
        ->assertCanSeeTableRecords([$shop]);
});
