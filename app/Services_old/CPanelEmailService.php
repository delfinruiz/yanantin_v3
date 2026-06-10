<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class CPanelEmailService
{
    public function __construct(
        private string $host,
        private string $username,
        private string $apiToken
    ) {}

    private function baseUrl(): string
    {
        return "https://{$this->host}:2083/json-api/cpanel";
    }

    private function uapiBaseUrl(): string
    {
        return "https://{$this->host}:2083/execute";
    }

    public function call(string $module, string $function, array $params = []): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'cpanel '.$this->username.':'.$this->apiToken,
        ])->post($this->baseUrl(), [
            'cpanel_jsonapi_user' => $this->username,
            'cpanel_jsonapi_apiversion' => '2',
            'cpanel_jsonapi_module' => $module,
            'cpanel_jsonapi_func' => $function,
            ...$params,
        ]);

        $json = method_exists($response, 'json') ? $response->json() : [];
        if (! $json) {
            $body = method_exists($response, 'body') ? $response->body() : '';
            $json = json_decode($body, true) ?? [];
        }

        if (isset($json['cpanelresult']['error'])) {
            throw new \Exception('Error API cPanel: '.$json['cpanelresult']['error']);
        }

        if (isset($json['cpanelresult']['data']) && is_string($json['cpanelresult']['data']) && str_contains(strtolower($json['cpanelresult']['data']), 'access denied')) {
            throw new \Exception('Acceso denegado cPanel: '.$json['cpanelresult']['data']);
        }

        return $json;
    }

    public function uapi(string $module, string $function, array $params = []): array
    {
        $url = $this->uapiBaseUrl()."/{$module}/{$function}";

        $response = Http::withHeaders([
            'Authorization' => 'cpanel '.$this->username.':'.$this->apiToken,
            'Accept' => 'application/json',
        ])->get($url, $params);

        $json = method_exists($response, 'json') ? $response->json() : [];
        if (! $json) {
            $body = method_exists($response, 'body') ? $response->body() : '';
            $json = json_decode($body, true) ?? [];
        }

        $resultNode = null;
        if (isset($json['result']) && is_array($json['result'])) {
            $resultNode = $json['result'];
        }

        $status = $resultNode['status'] ?? $json['status'] ?? null;
        if (is_bool($status)) {
            $status = $status ? 1 : 0;
        } elseif (is_string($status) && is_numeric($status)) {
            $status = (int) $status;
        }

        $errors = $resultNode['errors'] ?? $json['errors'] ?? null;

        $errorsList = [];
        if (is_string($errors) && trim($errors) !== '') {
            $errorsList[] = trim($errors);
        } elseif (is_array($errors)) {
            foreach ($errors as $e) {
                if (is_string($e) && trim($e) !== '') {
                    $errorsList[] = trim($e);
                }
            }
        }

        if (! empty($errorsList) || $status === 0) {
            $message = ! empty($errorsList) ? implode('; ', $errorsList) : 'La API devolvió status=0';

            throw new \Exception('Error UAPI cPanel: '.$message);
        }

        return $json;
    }

    public function create(string $email, string $password, int $quotaMb = 250): array
    {
        $domain = substr(strrchr($email, '@'), 1);
        $username = substr($email, 0, strrpos($email, '@'));

        return $this->call('Email', 'addpop', [
            'domain' => $domain,
            'email' => $username,
            'password' => $password,
            'quota' => $quotaMb,
        ]);
    }

    public function delete(string $email): array
    {
        $domain = substr(strrchr($email, '@'), 1);
        $username = substr($email, 0, strrpos($email, '@'));

        return $this->call('Email', 'delpop', [
            'domain' => $domain,
            'email' => $username,
        ]);
    }

    public function list(): array
    {
        $json = $this->call('Email', 'listpopswithdisk');

        return $json['cpanelresult']['data'] ?? [];
    }

    public function quotaInfo(): array
    {
        $json = $this->uapi('Quota', 'get_quota_info');

        $result = $json['result'] ?? null;
        if (is_array($result) && array_key_exists('data', $result)) {
            return is_array($result['data']) ? $result['data'] : [];
        }

        return is_array($json['data'] ?? null) ? $json['data'] : [];
    }

    public function countPops(): ?int
    {
        $json = $this->uapi('Email', 'count_pops');

        $result = $json['result'] ?? null;
        $data = null;

        if (is_array($result) && array_key_exists('data', $result)) {
            $data = $result['data'];
        } elseif (array_key_exists('data', $json)) {
            $data = $json['data'];
        }

        if (is_int($data)) {
            return $data;
        }

        if (is_string($data) && is_numeric($data)) {
            return (int) $data;
        }

        return null;
    }

    public function listPopsWithDisk(int $maxAccounts = 5000): array
    {
        $json = $this->uapi('Email', 'list_pops_with_disk', [
            'no_validate' => 1,
            'no_disk' => 0,
            'skip_main' => 0,
            'maxaccounts' => $maxAccounts,
        ]);

        $result = $json['result'] ?? null;
        if (is_array($result) && array_key_exists('data', $result)) {
            return is_array($result['data']) ? $result['data'] : [];
        }

        return is_array($json['data'] ?? null) ? $json['data'] : [];
    }

    public function mainAccountDiskUsageBytes(): ?float
    {
        $json = $this->uapi('Email', 'get_main_account_disk_usage_bytes');

        $result = $json['result'] ?? null;
        $data = null;

        if (is_array($result) && array_key_exists('data', $result)) {
            $data = $result['data'];
        } elseif (array_key_exists('data', $json)) {
            $data = $json['data'];
        }

        if (is_int($data) || is_float($data)) {
            return (float) $data;
        }

        if (is_string($data) && is_numeric(trim($data))) {
            return (float) trim($data);
        }

        return null;
    }

    public function changePassword(string $email, string $newPassword): array
    {
        $domain = substr(strrchr($email, '@'), 1);
        $username = substr($email, 0, strrpos($email, '@'));

        return $this->call('Email', 'passwdpop', [
            'domain' => $domain,
            'email' => $username,
            'password' => $newPassword,
        ]);
    }

    public function changeQuota(string $email, int $quotaMb): array
    {
        $domain = substr(strrchr($email, '@'), 1);
        $username = substr($email, 0, strrpos($email, '@'));

        return $this->call('Email', 'editquota', [
            'domain' => $domain,
            'email' => $username,
            'quota' => $quotaMb,
        ]);
    }

    public function diskInfo(): array
    {
        $json = $this->call('Fileman', 'getdiskinfo');

        $data = $json['cpanelresult']['data'] ?? [];
        if (! is_array($data) || $data === []) {
            return [];
        }

        $first = $data[0] ?? [];

        return is_array($first) ? $first : [];
    }
}
