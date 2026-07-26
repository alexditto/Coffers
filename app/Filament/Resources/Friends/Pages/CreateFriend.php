<?php

namespace App\Filament\Resources\Friends\Pages;

use App\Filament\Resources\Friends\FriendResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFriend extends CreateRecord
{
    protected static string $resource = FriendResource::class;
}
