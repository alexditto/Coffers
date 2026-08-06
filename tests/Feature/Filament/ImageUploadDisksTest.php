<?php

use App\Filament\Resources\Campaigns\Pages\CreateCampaign;
use App\Filament\Resources\Characters\Pages\CreateCharacter;
use App\Filament\Resources\Items\Pages\CreateItem;
use App\Filament\Resources\Shops\Pages\CreateShop;
use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Livewire\Livewire;

test('the shop image upload targets the s3 disk', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($admin)
        ->test(CreateShop::class)
        ->assertSchemaComponentExists('image', checkComponentUsing: fn (FileUpload $component) => $component->getDiskName() === 's3'
            && $component->getDirectory() === 'shops'
            && $component->getVisibility() === 'public');
});

test('the campaign image upload targets the s3 disk', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($admin)
        ->test(CreateCampaign::class)
        ->assertSchemaComponentExists('image', checkComponentUsing: fn (FileUpload $component) => $component->getDiskName() === 's3'
            && $component->getDirectory() === 'campaigns'
            && $component->getVisibility() === 'public');
});

test('the item image upload targets the s3 disk', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($admin)
        ->test(CreateItem::class)
        ->assertSchemaComponentExists('image', checkComponentUsing: fn (FileUpload $component) => $component->getDiskName() === 's3'
            && $component->getDirectory() === 'items'
            && $component->getVisibility() === 'public');
});

test('the character image upload targets the s3 disk', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($admin)
        ->test(CreateCharacter::class)
        ->assertSchemaComponentExists('image', checkComponentUsing: fn (FileUpload $component) => $component->getDiskName() === 's3'
            && $component->getDirectory() === 'characters'
            && $component->getVisibility() === 'public');
});
