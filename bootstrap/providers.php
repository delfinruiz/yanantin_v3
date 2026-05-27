<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\CentralPanelProvider;
use App\Providers\PlanShieldServiceProvider;
use App\Providers\TenancyServiceProvider;

return [
    AppServiceProvider::class,
    CentralPanelProvider::class,
    AdminPanelProvider::class,
    TenancyServiceProvider::class,
    PlanShieldServiceProvider::class,
];
