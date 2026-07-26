<?php

namespace App\Filament\Resources\Friends\Pages;

use App\Filament\Resources\Friends\FriendResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFriends extends ListRecords
{
    protected static string $resource = FriendResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
