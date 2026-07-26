<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\ChartWidget;

class NewUsersChart extends ChartWidget
{
    protected ?string $heading = 'New Users';

    protected function getData(): array
    {
        $months = collect(range(11, 0))
            ->map(fn (int $monthsAgo) => now()->subMonths($monthsAgo)->startOfMonth());

        $countsByMonth = User::query()
            ->where('role', '!=', 'admin')
            ->get()
            ->groupBy(fn (User $user) => $user->created_at->format('Y-m'))
            ->map->count();

        return [
            'datasets' => [
                [
                    'label' => 'New users',
                    'data' => $months->map(fn ($month) => $countsByMonth->get($month->format('Y-m'), 0))->all(),
                ],
            ],
            'labels' => $months->map(fn ($month) => $month->format('M Y'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
