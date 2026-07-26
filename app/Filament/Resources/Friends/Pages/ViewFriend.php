<?php

namespace App\Filament\Resources\Friends\Pages;

use App\Filament\Resources\Friends\FriendResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewFriend extends ViewRecord
{
    protected static string $resource = FriendResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
