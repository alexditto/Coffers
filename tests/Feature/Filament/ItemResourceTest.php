<?php

use App\Filament\Imports\ItemsImporter;
use App\Filament\Resources\Items\Pages\ListItems;
use App\Models\Item;
use App\Models\User;
use Filament\Actions\Imports\Models\Import;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

test('the items table lists items with their columns', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $item = Item::factory()->create([
        'name' => 'Longsword',
        'description' => 'A finely balanced blade.',
        'default_price' => 15,
    ]);

    Livewire::actingAs($admin)
        ->test(ListItems::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$item])
        ->assertSee('Longsword')
        ->assertSee('A finely balanced blade.');
});

test('the items table has an import action', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($admin)
        ->test(ListItems::class)
        ->assertActionExists(TestAction::make('import')->table());
});

test('the items importer maps name, description, image, and default_price', function () {
    $columns = collect(ItemsImporter::getColumns())->keyBy(fn ($column) => $column->getName());

    expect($columns->keys()->all())->toEqualCanonicalizing(['name', 'description', 'image', 'default_price'])
        ->and($columns['name']->isMappingRequired())->toBeTrue()
        ->and($columns['description']->isMappingRequired())->toBeFalse()
        ->and($columns['default_price']->isNumeric())->toBeTrue();
});

test('the items importer creates a new item for each row', function () {
    $importer = new ItemsImporter(
        import: new Import,
        columnMap: [],
        options: [],
    );

    expect($importer->resolveRecord())->toBeInstanceOf(Item::class)
        ->and($importer->resolveRecord()->exists)->toBeFalse();
});
