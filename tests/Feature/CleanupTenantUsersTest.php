<?php

use App\Jobs\CleanupTenantUsers;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('deletes all users belonging to tenant', function () {
    $tenant = Tenant::create([
        'id' => 'cleanup-users-test-'.fake()->uuid(),
        'name' => 'Cleanup Users Test Tenant',
    ]);

    $users = User::factory()->count(3)->create(['tenant_id' => $tenant->id]);
    $userIds = $users->pluck('id')->toArray();

    $job = new CleanupTenantUsers($tenant);
    $job->handle();

    foreach ($userIds as $userId) {
        expect(User::withoutGlobalScope('tenant')->find($userId))->toBeNull();
    }
});

it('does not delete users from other tenants', function () {
    $tenant1 = Tenant::create([
        'id' => 'tenant1-'.fake()->uuid(),
        'name' => 'Tenant 1',
    ]);

    $tenant2 = Tenant::create([
        'id' => 'tenant2-'.fake()->uuid(),
        'name' => 'Tenant 2',
    ]);

    $users1 = User::factory()->count(2)->create(['tenant_id' => $tenant1->id]);
    $users2 = User::factory()->count(2)->create(['tenant_id' => $tenant2->id]);

    $job = new CleanupTenantUsers($tenant1);
    $job->handle();

    foreach ($users1 as $user) {
        expect(User::withoutGlobalScope('tenant')->find($user->id))->toBeNull();
    }

    foreach ($users2 as $user) {
        expect(User::withoutGlobalScope('tenant')->find($user->id))->not->toBeNull();
    }
});

it('handles tenant with no users gracefully', function () {
    $tenant = Tenant::create([
        'id' => 'empty-tenant-'.fake()->uuid(),
        'name' => 'Empty Tenant',
    ]);

    $job = new CleanupTenantUsers($tenant);
    $job->handle();

    expect(true)->toBeTrue();
});
