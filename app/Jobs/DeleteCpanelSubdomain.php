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

class DeleteCpanelSubdomain implements ShouldQueue
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

        $auth = 'cpanel '.$username.':'.$token;
        $attempt = $this->attempts();

        if ($attempt > 1) {
            Log::info("cPanel: Reintentando eliminar subdominio {$fqdn} (intento {$attempt}/3)...");
        }

        try {
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
        } catch (ConnectionException $e) {
            Log::warning("cPanel: Error de conexion al eliminar {$fqdn} (intento {$attempt}/3): ".$e->getMessage());

            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        $subdomain = $this->tenant->domain_name
            ?? $this->tenant->domains()->first()?->domain
            ?? $this->tenant->id;

        Log::error("cPanel: Fallo permanente al eliminar subdominio para tenant {$this->tenant->id}. 3 reintentos agotados.", [
            'error' => $e->getMessage(),
            'subdomain' => $subdomain,
        ]);

        $this->notifyAdmins('eliminar', $subdomain, $e->getMessage());
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
