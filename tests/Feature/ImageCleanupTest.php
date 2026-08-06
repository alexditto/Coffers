<?php

use App\Models\Campaign;
use App\Models\Character;
use App\Models\Journal;
use App\Models\Scene;
use App\Models\Shop;
use Illuminate\Support\Facades\Storage;

test('replacing a character image deletes the old one from s3 but keeps the new one', function () {
    Storage::fake('s3');

    Storage::disk('s3')->put('characters/old.jpg', 'old bytes');
    $oldUrl = 'https://coffer-general.s3.us-east-2.amazonaws.com/characters/old.jpg';

    $character = Character::factory()->create(['image' => $oldUrl]);

    Storage::disk('s3')->put('characters/new.jpg', 'new bytes');
    $newUrl = 'https://coffer-general.s3.us-east-2.amazonaws.com/characters/new.jpg';

    $character->update(['image' => $newUrl]);

    Storage::disk('s3')->assertMissing('characters/old.jpg');
    Storage::disk('s3')->assertExists('characters/new.jpg');
});

test('deleting a character removes its current image from s3', function () {
    Storage::fake('s3');

    Storage::disk('s3')->put('characters/current.jpg', 'bytes');
    $url = 'https://coffer-general.s3.us-east-2.amazonaws.com/characters/current.jpg';

    $character = Character::factory()->create(['image' => $url]);

    $character->delete();

    Storage::disk('s3')->assertMissing('characters/current.jpg');
});

test('saving a character without changing the image does not touch s3', function () {
    Storage::fake('s3');

    Storage::disk('s3')->put('characters/current.jpg', 'bytes');
    $url = 'https://coffer-general.s3.us-east-2.amazonaws.com/characters/current.jpg';

    $character = Character::factory()->create(['image' => $url]);

    $character->update(['name' => 'Renamed']);

    Storage::disk('s3')->assertExists('characters/current.jpg');
});

test('a legacy external image url is left alone on update and delete', function () {
    Storage::fake('s3');

    $character = Character::factory()->create(['image' => 'https://example.com/legacy.jpg']);

    // If the observer mistook this for one of our own S3 paths, deleting an
    // empty/garbage key would throw - reaching these assertions proves it didn't.
    $character->update(['image' => null]);
    expect($character->fresh()->image)->toBeNull();

    $character->delete();
    expect(Character::find($character->id))->toBeNull();
});

test('the image observer is attached to campaigns, shops, scenes, and journals', function (string $modelClass) {
    Storage::fake('s3');

    Storage::disk('s3')->put('test/old.jpg', 'old bytes');
    $oldUrl = 'https://coffer-general.s3.us-east-2.amazonaws.com/test/old.jpg';

    $record = $modelClass::factory()->create(['image' => $oldUrl]);

    Storage::disk('s3')->put('test/new.jpg', 'new bytes');
    $newUrl = 'https://coffer-general.s3.us-east-2.amazonaws.com/test/new.jpg';

    $record->update(['image' => $newUrl]);

    Storage::disk('s3')->assertMissing('test/old.jpg');

    $record->delete();

    Storage::disk('s3')->assertMissing('test/new.jpg');
})->with([
    Campaign::class,
    Shop::class,
    Scene::class,
    Journal::class,
]);
