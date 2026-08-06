<?php

use App\Models\Campaign;
use App\Models\Character;
use App\Models\Journal;
use App\Models\Scene;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('uploading an image for a new character stores it on s3 and saves the url', function () {
    Storage::fake('s3');

    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('thornwick.jpg');

    Livewire::actingAs($user)
        ->test('character-builder-page')
        ->call('newCharacter')
        ->set('characterName', 'Thornwick')
        ->set('characterImage', $file)
        ->call('save')
        ->assertHasNoErrors();

    $character = Character::where('user_id', $user->id)->where('name', 'Thornwick')->firstOrFail();

    expect($character->image)->not->toBeNull()
        ->and(Storage::disk('s3')->allFiles('characters'))->toHaveCount(1);
});

test('editing a character without selecting a new image keeps the existing image', function () {
    Storage::fake('s3');

    $user = User::factory()->create();
    $character = Character::factory()->create(['user_id' => $user->id, 'image' => 'https://bucket.s3.amazonaws.com/characters/original.jpg']);

    Livewire::actingAs($user)
        ->test('character-builder-page')
        ->call('edit', $character->id)
        ->set('characterName', 'Renamed')
        ->call('save')
        ->assertHasNoErrors();

    expect($character->fresh()->image)->toBe('https://bucket.s3.amazonaws.com/characters/original.jpg');
    Storage::disk('s3')->assertDirectoryEmpty('characters');
});

test('uploading an image for a campaign stores it on s3 and saves the url', function () {
    Storage::fake('s3');

    $user = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $user->id]);
    $file = UploadedFile::fake()->image('campaign.jpg');

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($user)
        ->test('campaign-details')
        ->set('image', $file)
        ->call('updateDetails')
        ->assertHasNoErrors();

    expect($campaign->fresh()->image)->not->toBeNull()
        ->and(Storage::disk('s3')->allFiles('campaigns'))->toHaveCount(1);
});

test('uploading an image for a new shop stores it on s3 and saves the url', function () {
    Storage::fake('s3');

    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $file = UploadedFile::fake()->image('shop.jpg');

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('d-m-shop-page')
        ->call('newShop')
        ->set('shopName', 'The Iron Anvil')
        ->set('shopImage', $file)
        ->call('saveShop')
        ->assertHasNoErrors();

    $shop = Shop::where('name', 'The Iron Anvil')->firstOrFail();

    expect($shop->image)->not->toBeNull()
        ->and(Storage::disk('s3')->allFiles('shops'))->toHaveCount(1);
});

test('uploading an image for a new scene stores it on s3 and saves the url', function () {
    Storage::fake('s3');

    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $file = UploadedFile::fake()->image('scene.jpg');

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('scenes-page')
        ->call('newScene')
        ->set('sceneName', 'The Sunken Crypt')
        ->set('sceneImage', $file)
        ->call('saveScene')
        ->assertHasNoErrors();

    $scene = Scene::where('name', 'The Sunken Crypt')->firstOrFail();

    expect($scene->image)->not->toBeNull()
        ->and(Storage::disk('s3')->allFiles('scenes'))->toHaveCount(1);
});

test('uploading an image for a new journal entry stores it on s3 and saves the url', function () {
    Storage::fake('s3');

    $owner = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $owner->id]);
    $file = UploadedFile::fake()->image('entry.jpg');

    session(['selected_campaign_id' => $campaign->id]);

    Livewire::actingAs($owner)
        ->test('journal-page')
        ->call('newEntry')
        ->set('entryTitle', 'The Sunken Crypt')
        ->set('entryContent', 'A forgotten crypt beneath the old chapel.')
        ->set('entryImage', $file)
        ->call('saveEntry')
        ->assertHasNoErrors();

    $entry = Journal::where('title', 'The Sunken Crypt')->firstOrFail();

    expect($entry->image)->not->toBeNull()
        ->and(Storage::disk('s3')->allFiles('journals'))->toHaveCount(1);
});
