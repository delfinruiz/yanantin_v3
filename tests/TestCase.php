<?php

namespace Tests;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\File;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected ?Tenant $currentTenant = null;

    protected function initializeTenancy(): void
    {
        $tenantId = 'test-'.fake()->uuid();

        $tenant = Tenant::create([
            'id' => $tenantId,
            'name' => 'Test Tenant',
        ]);

        $tenant->domains()->create(['domain' => $tenantId]);

        tenancy()->initialize($tenant);
        $this->currentTenant = $tenant;
    }

    protected function tearDown(): void
    {
        if ($this->currentTenant) {
            tenancy()->end();

            $slug = $this->currentTenant->domain_name
                ?? $this->currentTenant->domains()->first()?->domain
                ?? $this->currentTenant->id;

            foreach ([resource_path('views/tenants/'.$slug), public_path('tenants/'.$slug)] as $dir) {
                if (File::isDirectory($dir)) {
                    File::deleteDirectory($dir);
                }
            }

            $this->currentTenant = null;
        }

        $this->cleanTestDirectories();

        parent::tearDown();
    }

    protected function cleanTestDirectories(): void
    {
        $patterns = ['test-*', 'my-tenant-*', 'new-tenant-*'];

        foreach ([resource_path('views/tenants'), public_path('tenants')] as $base) {
            foreach ($patterns as $pattern) {
                foreach (File::glob($base.'/'.$pattern) as $dir) {
                    File::deleteDirectory($dir);
                }
            }
        }
    }
}
