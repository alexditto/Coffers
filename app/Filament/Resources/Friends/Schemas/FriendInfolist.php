<?php

namespace App\Filament\Resources\Friends\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class FriendInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('user.name')
                    ->label('User'),
                TextEntry::make('friend.name')
                    ->label('Friend'),
                TextEntry::make('status'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
