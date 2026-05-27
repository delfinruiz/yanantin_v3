<?php

use App\Models\Tenant;
use App\Models\User;

it('can list tenants', function () {
    $user = User::factory()->create();

    $tenant = Tenant::create([
        'id' => 'my-tenant-'.fake()->uuid(),
        'name' => 'My Tenant',
    ]);
    $tenant->domains()->create(['domain' => 'my-test-'.fake()->uuid()]);

    $this->actingAs($user)
        ->get('http://localhost/central/tenants')
        ->assertOk();
});

it('can access tenant creation page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('http://localhost/central/tenants/create')
        ->assertOk();
});

it('can create a tenant via model', function () {
    $tenantId = 'new-tenant-'.fake()->uuid();

    $tenant = Tenant::create([
        'id' => $tenantId,
        'name' => 'New Tenant',
        'plan' => 'basic',
    ]);
    $tenant->domains()->create(['domain' => 'new-tenant-'.fake()->uuid()]);

    expect($tenant)->not->toBeNull()
        ->and($tenant->name)->toBe('New Tenant')
        ->and($tenant->plan)->toBe('basic')
        ->and($tenant->domains()->first()->domain)->not->toBeEmpty();
});
