<?php

namespace App\Observers;

use App\Jobs\SyncTenantPermissions;
use App\Models\Plan;

class PlanObserver
{
    public function updated(Plan $plan): void
    {
        if (! $plan->wasChanged('features')) {
            return;
        }

        $plan->load('tenants');

        foreach ($plan->tenants as $tenant) {
            app()->isProduction()
                ? SyncTenantPermissions::dispatch($tenant)->onQueue('default')
                : SyncTenantPermissions::dispatchSync($tenant);
        }
    }
}
