<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlanInfo extends StatsOverviewWidget
{
    protected static ?int $sort = -2;

    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        return (bool) auth()->user()?->is_internal;
    }

    protected function getStats(): array
    {
        $plan = tenant()?->tenantPlan;
        $features = $plan?->features ?? [];
        $featureLabels = collect(config('plans.features', []))
            ->only($features)
            ->pluck('label')
            ->toArray();

        return [
            Stat::make('Plan contratado', $plan?->name ?? 'Sin plan')
                ->icon('heroicon-o-credit-card')
                ->color('primary')
                ->description(implode(', ', $featureLabels)),
        ];
    }
}
