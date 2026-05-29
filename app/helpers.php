<?php

use App\Services\TenantTimezoneService;
use Carbon\Carbon;

if (! function_exists('tenant_timezone')) {
    /**
     * Get the TenantTimezoneService instance.
     */
    function tenant_timezone(): TenantTimezoneService
    {
        return app(TenantTimezoneService::class);
    }
}

if (! function_exists('tenant_tz')) {
    /**
     * Get the timezone string for the current tenant.
     */
    function tenant_tz(): string
    {
        return app(TenantTimezoneService::class)->timezone();
    }
}

if (! function_exists('tenant_now')) {
    /**
     * Get the current datetime in the tenant's timezone.
     */
    function tenant_now(): Carbon
    {
        return app(TenantTimezoneService::class)->now();
    }
}
