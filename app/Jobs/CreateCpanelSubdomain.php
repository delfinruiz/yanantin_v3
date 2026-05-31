<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Models\User;
use App\Notifications\CpanelSubdomainFailed;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class CreateCpanelSubdomain implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [15, 30, 60];

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
        $attempt = $this->attempts();

        if ($attempt > 1) {
            Log::info("cPanel: Reintentando crear subdominio {$fqdn} (intento {$attempt}/3)...");
        }

        try {
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
        } catch (ConnectionException $e) {
            Log::warning("cPanel: Error de conexion al crear {$fqdn} (intento {$attempt}/3): ".$e->getMessage());

            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        $subdomain = $this->tenant->domain_name
            ?? $this->tenant->domains()->first()?->domain
            ?? $this->tenant->id;

        Log::error("cPanel: Fallo permanente al crear subdominio para tenant {$this->tenant->id}. 3 reintentos agotados.", [
            'error' => $e->getMessage(),
            'subdomain' => $subdomain,
        ]);

        $this->notifyAdmins('crear', $subdomain, $e->getMessage());
    }

    private function notifyAdmins(string $action, string $subdomain, string $error): void
    {
        $admins = User::whereNull('tenant_id')
            ->whereHas('roles', fn ($q) => $q->where('name', 'super_admin'))
            ->get();

        if ($admins->isEmpty()) {
            return;
        }

        Notification::send(
            $admins,
            new CpanelSubdomainFailed($this->tenant->id, $subdomain, $action, $error)
        );
    }
}
