<?php

namespace App\Jobs;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeleteCpanelSubdomain implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected Tenant $tenant,
    ) {}

    public function handle(): void
    {
        if (! config('services.cpanel.enabled')) {
            return;
        }

        $subdomain = $this->tenant->domain_name
            ?? $this->tenant->domains()->first()?->domain;

        if (! $subdomain) {
            Log::warning('cPanel: No se pudo determinar el subdominio del tenant '.$this->tenant->id);

            return;
        }

        $rootDomain = config('services.cpanel.root_domain');
        $fqdn = "{$subdomain}.{$rootDomain}";
        $host = config('services.cpanel.host');
        $username = config('services.cpanel.username');
        $token = config('services.cpanel.token');

        $auth = 'cpanel '.$username.':'.$token;

        $response = Http::withHeaders(['Authorization' => $auth])
            ->withoutVerifying()
            ->timeout(30)
            ->connectTimeout(10)
            ->get("https://{$host}:2083/json-api/cpanel", [
                'cpanel_jsonapi_module' => 'SubDomain',
                'cpanel_jsonapi_func' => 'delsubdomain',
                'cpanel_jsonapi_apiversion' => 2,
                'domain' => $subdomain,
                'rootdomain' => $rootDomain,
            ]);

        $body = $response->json();

        $errors = $body['errors'] ?? $body['data'][0]['result']['errors'] ?? [];

        if ($response->failed() || ! empty($errors)) {
            Log::error('cPanel: No se pudo eliminar el subdominio '.$fqdn, [
                'status' => $response->status(),
                'response' => $body,
            ]);

            return;
        }

        Log::info('cPanel: Subdominio eliminado: '.$fqdn);
    }
}
