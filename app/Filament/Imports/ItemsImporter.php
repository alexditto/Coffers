<?php

namespace App\Filament\Imports;

use App\Models\Item;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class ItemsImporter extends Importer
{
    protected static ?string $model = Item::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'max:255'])
                ->example('Longsword'),
            ImportColumn::make('description')
                ->rules(['nullable', 'max:255'])
                ->example('A finely balanced blade.'),
            ImportColumn::make('image')
                ->rules(['nullable', 'max:2048'])
                ->example('https://example.com/longsword.png'),
            ImportColumn::make('default_price')
                ->numeric()
                ->integer()
                ->rules(['nullable', 'integer', 'min:0'])
                ->example('15'),
        ];
    }

    public function resolveRecord(): Item
    {
        return new Item;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your items import has completed and '.Number::format($import->successful_rows).' '.str('row')->plural($import->successful_rows).' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to import.';
        }

        return $body;
    }
}
