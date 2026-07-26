<?php

namespace App\Filament\Resources\Friends;

use App\Filament\Resources\Friends\Pages\CreateFriend;
use App\Filament\Resources\Friends\Pages\EditFriend;
use App\Filament\Resources\Friends\Pages\ListFriends;
use App\Filament\Resources\Friends\Pages\ViewFriend;
use App\Filament\Resources\Friends\Schemas\FriendForm;
use App\Filament\Resources\Friends\Schemas\FriendInfolist;
use App\Filament\Resources\Friends\Tables\FriendsTable;
use App\Models\Friend;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FriendResource extends Resource
{
    protected static ?string $model = Friend::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return FriendForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return FriendInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FriendsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFriends::route('/'),
            'create' => CreateFriend::route('/create'),
            //            'view' => ViewFriend::route('/{record}'),
            'edit' => EditFriend::route('/{record}/edit'),
        ];
    }
}
