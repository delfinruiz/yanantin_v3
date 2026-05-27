<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserStats extends StatsOverviewWidget
{
    protected static ?int $sort = -2;

    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        return (bool) auth()->user()?->is_internal;
    }

    protected function getStats(): array
    {
        $total = User::count();
        $limit = tenant()->maxUsers();
        $label = $limit ? "{$total} / {$limit}" : "{$total}";
        $description = $limit ? 'Usuarios del plan' : 'Usuarios (ilimitado)';
        $color = $limit && $total >= $limit ? 'danger' : ($limit && $total >= ($limit * 0.8) ? 'warning' : 'success');

        return [
            Stat::make('Total usuarios', $label)
                ->description($description)
                ->icon('heroicon-o-users')
                ->color($color),
        ];
    }
}
