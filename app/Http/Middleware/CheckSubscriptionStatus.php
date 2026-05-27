<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscriptionStatus
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! tenancy()->initialized) {
            return $next($request);
        }

        $tenant = tenant();

        if ($tenant->isActive()) {
            return $next($request);
        }

        $view = $tenant->isSuspended() ? 'tenant.suspended' : 'tenant.paused';

        return response()->view($view, [
            'tenant' => $tenant,
        ]);
    }
}
