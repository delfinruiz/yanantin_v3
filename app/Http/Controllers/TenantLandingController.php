<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\View;

class TenantLandingController extends Controller
{
    public function show()
    {
        $tenant = tenant();
        $slug = $this->getTenantSlug($tenant);

        $customView = 'tenants.'.$slug.'.landing';

        if (View::exists($customView)) {
            return view($customView, [
                'tenant' => $tenant,
            ]);
        }

        $html = $tenant->landing_page_html;

        if ($html) {
            return view('tenant.landing', [
                'html' => $html,
                'css' => $tenant->landing_page_css ?? '',
                'tenant' => $tenant,
            ]);
        }

        return view('tenant.default-landing', [
            'tenant' => $tenant,
        ]);
    }

    protected function getTenantSlug($tenant): string
    {
        return $tenant->domain_name
            ?? $tenant->domains()->first()?->domain
            ?? $tenant->id;
    }
}
