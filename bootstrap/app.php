<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Stancl\Tenancy\Contracts\TenantCouldNotBeIdentifiedException;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedOnDomainException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->group('universal', []);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontReport([
            TenantCouldNotBeIdentifiedOnDomainException::class,
        ]);

        $exceptions->render(function (TenantCouldNotBeIdentifiedOnDomainException $e) {
            return response()->view('tenant.not-found', [], 404);
        });

        $exceptions->render(function (TenantCouldNotBeIdentifiedException $e) {
            return response()->view('tenant.not-found', [], 404);
        });
    })->create();
