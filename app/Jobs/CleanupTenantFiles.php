<?php

namespace App\Jobs;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class CleanupTenantFiles implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected Tenant $tenant,
    ) {}

    public function handle(): void
    {
        try {
            $slug = $this->tenant->domain_name
                ?? $this->tenant->domains()->first()?->domain
                ?? $this->tenant->id;

            $viewDirectory = resource_path('views/tenants/'.$slug);

            if (File::isDirectory($viewDirectory)) {
                File::deleteDirectory($viewDirectory);
            }

            $publicDirectory = public_path('tenants/'.$slug);

            if (File::isDirectory($publicDirectory)) {
                File::deleteDirectory($publicDirectory);
            }
        } catch (\Exception $e) {
            Log::warning('No se pudo eliminar directorios del tenant '.$this->tenant->id.': '.$e->getMessage());
        }
    }
}
