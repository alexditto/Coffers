<?php

namespace App\Filament\Resources\Characters\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CharacterInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('user.name')
                    ->label('User'),
                TextEntry::make('name'),
                ImageEntry::make('image')
                    ->placeholder('-'),
                TextEntry::make('campaign.name')
                    ->label('Campaign')
                    ->placeholder('-'),
                TextEntry::make('inventory.id')
                    ->label('Inventory')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),

                Section::make('Character Sheet')
                    ->columns(2)
                    ->components([
                        TextEntry::make('character_sheet.class')
                            ->label('Class')
                            ->placeholder('-'),
                        TextEntry::make('character_sheet.race')
                            ->label('Race')
                            ->placeholder('-'),
                        TextEntry::make('character_sheet.level')
                            ->label('Level')
                            ->placeholder('-'),
                        TextEntry::make('character_sheet.alignment')
                            ->label('Alignment')
                            ->placeholder('-'),
                        TextEntry::make('character_sheet.background')
                            ->label('Background')
                            ->placeholder('-'),
                        TextEntry::make('character_sheet.statuses.name')
                            ->label('Conditions')
                            ->badge()
                            ->placeholder('-'),
                        TextEntry::make('character_sheet.health')
                            ->label('Health')
                            ->placeholder('-'),
                        TextEntry::make('character_sheet.total_health')
                            ->label('Total health')
                            ->placeholder('-'),
                        TextEntry::make('character_sheet.ac')
                            ->label('AC')
                            ->placeholder('-'),
                        TextEntry::make('character_sheet.description')
                            ->label('Description')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
