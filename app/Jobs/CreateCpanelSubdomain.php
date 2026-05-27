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

class CreateCpanelSubdomain implements ShouldQueue
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
        $documentRoot = config('services.cpanel.document_root');

        $auth = 'cpanel '.$username.':'.$token;

        $response = Http::withHeaders(['Authorization' => $auth])
            ->withoutVerifying()
            ->timeout(30)
            ->connectTimeout(10)
            ->get("https://{$host}:2083/execute/SubDomain/addsubdomain", [
                'domain' => $subdomain,
                'rootdomain' => $rootDomain,
                'dir' => $documentRoot,
                'disallowdot' => 0,
            ]);

        $body = $response->json();

        if ($response->failed() || ! empty($body['errors'])) {
            Log::error('cPanel: No se pudo crear el subdominio '.$fqdn, [
                'status' => $response->status(),
                'response' => $body,
            ]);

            return;
        }

        Log::info('cPanel: Subdominio creado: '.$fqdn);
    }
}
