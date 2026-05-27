<?php

use App\Jobs\SeedTenantAdmin;
use App\Models\Tenant;
use App\Models\User;

it('does not fail when recreating tenant with same admin email', function () {
    $tenant = Tenant::create([
        'id' => 'recreate-test-'.fake()->uuid(),
        'name' => 'Recreate Test Tenant',
    ]);
    $tenant->domains()->create(['domain' => 'recreate-test']);

    $job = new SeedTenantAdmin($tenant);
    $job->handle();

    $adminEmail = 'admin@recreate-test.localhost';
    expect(User::withoutGlobalScope('tenant')->where('email', $adminEmail)->exists())->toBeTrue();

    // Simulate tenant deletion and recreation
    $tenant->delete();

    $newTenant = Tenant::create([
        'id' => 'recreate-test-new-'.fake()->uuid(),
        'name' => 'Recreate Test Tenant New',
    ]);
    $newTenant->domains()->create(['domain' => 'recreate-test']);

    // This should not throw an exception
    $job2 = new SeedTenantAdmin($newTenant);
    $job2->handle();

    expect(User::withoutGlobalScope('tenant')->where('email', $adminEmail)->count())->toBe(1);
});
