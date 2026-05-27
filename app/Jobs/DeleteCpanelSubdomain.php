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
            ->get("https://{$host}:2083/execute/SubDomain/delsubdomain", [
                'domain' => $fqdn,
            ]);

        $body = $response->json();

        if ($response->failed() || ! empty($body['errors'])) {
            Log::error('cPanel: No se pudo eliminar el subdominio '.$fqdn, [
                'status' => $response->status(),
                'response' => $body,
            ]);

            return;
        }

        Log::info('cPanel: Subdominio eliminado: '.$fqdn);
    }
}
