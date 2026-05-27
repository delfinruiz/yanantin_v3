<?php

namespace App\Observers;

use App\Jobs\SyncTenantPermissions;
use App\Models\Tenant;

class TenantObserver
{
    public function updated(Tenant $tenant): void
    {
        if (! $tenant->wasChanged('plan_id')) {
            return;
        }

        app()->isProduction()
            ? SyncTenantPermissions::dispatch($tenant)->onQueue('default')
            : SyncTenantPermissions::dispatchSync($tenant);
    }
}
