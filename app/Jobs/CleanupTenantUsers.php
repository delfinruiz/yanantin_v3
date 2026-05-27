<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CleanupTenantUsers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected Tenant $tenant,
    ) {}

    public function handle(): void
    {
        $count = User::withoutGlobalScope('tenant')->where('tenant_id', $this->tenant->id)->count();

        if ($count === 0) {
            return;
        }

        User::withoutGlobalScope('tenant')->where('tenant_id', $this->tenant->id)->delete();

        Log::info("CleanupTenantUsers: Eliminados {$count} usuarios del tenant {$this->tenant->id}");
    }
}
