<?php

namespace App\Filament\Resources\Shops\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ShopForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('status')
                    ->required()
                    ->default('active'),
                TextInput::make('description'),
                FileUpload::make('image')
                    ->image(),
                Select::make('owner_id')
                    ->relationship('owner', 'name')
                    ->required(),
            ]);
    }
}
