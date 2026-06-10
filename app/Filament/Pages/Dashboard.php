<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CompanyHappinessBarWidget;
use App\Filament\Widgets\DailyMoodWidget;
use App\Filament\Widgets\SetupChecklistWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getColumns(): int|array
    {
        return [
            'sm' => 1,
            'xl' => 12,
        ];
    }

    public function getWidgets(): array
    {
        $widgets = [];
        $user = auth()->user();

        if ($user?->is_internal) {
            $widgets[] = SetupChecklistWidget::class;
        }

        if (tenant()?->hasEntity('CompanyHappinessBarWidget') && $user?->can('View:CompanyHappinessBarWidget')) {
            $widgets[] = CompanyHappinessBarWidget::class;
        }

        if (tenant()?->hasEntity('DailyMoodWidget') && $user?->can('View:DailyMoodWidget')) {
            $widgets[] = DailyMoodWidget::class;
        }

        return $widgets;
    }
}
