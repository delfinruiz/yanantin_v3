<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class LandingFolderWarning extends Widget
{
    protected string $view = 'filament.widgets.landing-folder-warning';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->is_internal && filled(tenant()?->landing_dir_error);
    }
}
