<?php

namespace App\Filament\Resources\Characters\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CharacterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                FileUpload::make('image')
                    ->image(),
                Select::make('campaign_id')
                    ->relationship('campaign', 'name'),
                Select::make('inventory_id')
                    ->relationship('inventory', 'id'),

                Section::make('Character Sheet')
                    ->relationship('character_sheet')
                    ->columns(2)
                    ->components([
                        TextInput::make('class'),
                        TextInput::make('race'),
                        TextInput::make('level')
                            ->numeric()
                            ->default(1),
                        TextInput::make('alignment'),
                        TextInput::make('background'),
                        TextInput::make('status'),
                        TextInput::make('health')
                            ->numeric(),
                        TextInput::make('total_health')
                            ->numeric()
                            ->default(100),
                        TextInput::make('ac')
                            ->numeric()
                            ->default(10),
                        Textarea::make('description')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
