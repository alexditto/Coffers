<?php

namespace App\Filament\Resources\Friends\Pages;

use App\Filament\Resources\Friends\FriendResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditFriend extends EditRecord
{
    protected static string $resource = FriendResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
