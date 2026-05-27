<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProductStats extends StatsOverviewWidget
{
    protected static ?int $sort = -1;

    protected ?string $pollingInterval = '30s';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user?->can('ViewAny:Product') ?? false;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Total productos', Product::count())
                ->icon('heroicon-o-shopping-bag')
                ->color('primary'),

            Stat::make('Productos activos', Product::where('is_active', true)->count())
                ->icon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Sin stock', Product::where('stock', 0)->count())
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger'),
        ];
    }
}
