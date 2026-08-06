<?php

namespace App\Filament\Resources\Campaigns\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CampaignForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('description'),
                FileUpload::make('image')
                    ->image()
                    ->disk('s3')
                    ->directory('campaigns')
                    ->visibility('public'),
                TextInput::make('status')
                    ->required()
                    ->default('active'),
                Select::make('owner_id')
                    ->relationship('owner', 'name')
                    ->required(),
                DatePicker::make('next_session_date'),
            ]);
    }
}
