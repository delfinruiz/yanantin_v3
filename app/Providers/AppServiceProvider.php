<?php

namespace App\Providers;

use App\Models\Plan;
use App\Models\Tenant;
use App\Observers\PlanObserver;
use App\Observers\TenantObserver;
use App\Services\CPanelEmailService;
use App\Services\TenantTimezoneService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TenantTimezoneService::class);

        $this->app->singleton(CPanelEmailService::class, function ($app) {
            $host = tenant()?->cpanel_host ?: config('cpanel.host') ?: '';
            $username = tenant()?->cpanel_user ?: config('cpanel.username') ?: '';
            $token = tenant()?->cpanel_token ?: config('cpanel.token') ?: '';

            return new CPanelEmailService($host, $username, $token);
        });
    }

    public function boot(): void
    {
        Plan::observe(PlanObserver::class);
        Tenant::observe(TenantObserver::class);

        View::prependNamespace('filament', resource_path('views/filament'));
    }
}
