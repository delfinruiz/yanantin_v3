<?php

use App\Jobs\DeleteCpanelSubdomain;
use App\Models\Tenant;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['services.cpanel.enabled' => true]);
    config(['services.cpanel.host' => 'cahilt.com']);
    config(['services.cpanel.username' => 'testuser']);
    config(['services.cpanel.token' => 'testtoken']);
    config(['services.cpanel.root_domain' => 'cahilt.com']);
});

it('dispatches DeleteCpanelSubdomain job when tenant is deleted', function () {
    Http::fake([
        '*/execute/SubDomain/delsubdomain*' => Http::response([
            'status' => 1,
            'errors' => null,
        ]),
    ]);

    $tenant = Tenant::create([
        'id' => 'delete-test-'.fake()->uuid(),
        'name' => 'Delete Test Tenant',
    ]);
    $tenant->domains()->create(['domain' => 'delete-test-tenant']);

    // Dispatch the job directly (in production it's dispatched via TenantDeleted event)
    DeleteCpanelSubdomain::dispatchSync($tenant);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'SubDomain/delsubdomain')
            && $request['domain'] === 'delete-test-tenant.cahilt.com';
    });
});

it('calls cPanel API to delete subdomain', function () {
    Http::fake([
        '*/execute/SubDomain/delsubdomain*' => Http::response([
            'status' => 1,
            'errors' => null,
        ]),
    ]);

    $tenant = Tenant::create([
        'id' => 'api-test-'.fake()->uuid(),
        'name' => 'API Test Tenant',
    ]);
    $tenant->domains()->create(['domain' => 'api-test-tenant']);

    $job = new DeleteCpanelSubdomain($tenant);
    $job->handle();

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'SubDomain/delsubdomain')
            && $request['domain'] === 'api-test-tenant.cahilt.com';
    });
});

it('does not call cPanel API when disabled', function () {
    config(['services.cpanel.enabled' => false]);

    Http::fake();

    $tenant = Tenant::create([
        'id' => 'disabled-test-'.fake()->uuid(),
        'name' => 'Disabled Test Tenant',
    ]);
    $tenant->domains()->create(['domain' => 'disabled-test-tenant']);

    $job = new DeleteCpanelSubdomain($tenant);
    $job->handle();

    Http::assertNothingSent();
});

it('logs warning when subdomain cannot be determined', function () {
    Log::spy();

    $tenant = Tenant::create([
        'id' => 'no-domain-test-'.fake()->uuid(),
        'name' => 'No Domain Test Tenant',
    ]);

    $job = new DeleteCpanelSubdomain($tenant);
    $job->handle();

    Log::shouldHaveReceived('warning')
        ->with('cPanel: No se pudo determinar el subdominio del tenant '.$tenant->id);
});
