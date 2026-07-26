<?php

namespace App\Filament\Widgets;

use App\Models\Campaign;
use App\Models\Character;
use App\Models\Shop;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Users', User::where('role', '!=', 'admin')->count()),
            Stat::make('Campaigns', Campaign::count()),
            Stat::make('Characters', Character::count()),
            Stat::make('Shops', Shop::count()),
        ];
    }
}
