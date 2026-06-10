<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class CPanelFilemanService
{
    public function __construct(
        private string $host,
        private string $username,
        private string $apiToken,
        private string $password,
    ) {}

    private function jsonUrl(): string
    {
        return "https://{$this->host}:2083/json-api/cpanel";
    }

    private function uapiUrl(): string
    {
        return "https://{$this->host}:2083/execute";
    }

    private function headers(): array
    {
        return [
            'Authorization' => 'cpanel '.$this->username.':'.$this->apiToken,
        ];
    }

    public function call(string $module, string $function, array $params = []): array
    {
        $response = Http::withHeaders($this->headers())->post($this->jsonUrl(), [
            'cpanel_jsonapi_user' => $this->username,
            'cpanel_jsonapi_apiversion' => '2',
            'cpanel_jsonapi_module' => $module,
            'cpanel_jsonapi_func' => $function,
            ...$params,
        ]);

        $json = method_exists($response, 'json') ? $response->json() : [];

        if ($json === null) {
            throw new \Exception('La API JSON de cPanel devolvió una respuesta vacía o inválida (HTTP '.$response->status().').');
        }

        if (isset($json['cpanelresult']['error'])) {
            throw new \Exception('Error API cPanel: '.$json['cpanelresult']['error']);
        }

        return $json;
    }

    public function uapi(string $module, string $function, array $params = []): array
    {
        $response = Http::withHeaders($this->headers())
            ->get($this->uapiUrl()."/{$module}/{$function}", $params);

        $json = method_exists($response, 'json') ? $response->json() : [];

        if ($json === null) {
            throw new \Exception('La API de cPanel devolvió una respuesta vacía o inválida (HTTP '.$response->status().'). Verifica que el nombre de la función «'.$function.'» sea correcto.');
        }

        $resultNode = $json['result'] ?? null;

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

    public function listFiles(string $dir): array
    {
        $json = $this->call('Fileman', 'listfiles', [
            'dir' => $dir,
            'show_hidden' => '0',
        ]);

        return $json['cpanelresult']['data'] ?? [];
    }

    public function createFolder(string $parentDir, string $name): void
    {
        $this->call('Fileman', 'mkdir', [
            'path' => $parentDir,
            'name' => $name,
        ]);
    }

    public function getFileContent(string $dir, string $file): ?string
    {
        $path = rtrim($dir, '/').'/'.ltrim($file, '/');

        $endpoints = [
            'webdav_2078' => "https://{$this->host}:2078{$path}",
            'webdav_2077' => "http://{$this->host}:2077{$path}",
            'cpanel_2083' => "https://{$this->host}:2083{$path}",
        ];

        foreach ($endpoints as $method => $url) {
            try {
                $response = Http::withBasicAuth($this->username, $this->password)
                    ->timeout(120)
                    ->get($url);
            } catch (\Exception $e) {
                logger()->info('getFileContent: connection failed', [
                    'method' => $method,
                    'path' => $path,
                    'error' => $e->getMessage(),
                ]);

                continue;
            }

            if ($response->successful()) {
                $body = $response->body();
                $contentType = $response->header('Content-Type') ?? '';

                if ($body !== '' && ! str_starts_with($contentType, 'text/html')) {
                    return $body;
                }

                logger()->info('getFileContent: empty or html response', [
                    'method' => $method,
                    'path' => $path,
                    'content_type' => $contentType,
                    'body_len' => strlen($body),
                ]);
            }

            logger()->info('getFileContent: tried', [
                'method' => $method,
                'path' => $path,
                'status' => $response->status(),
            ]);
        }

        return null;
    }

    public function saveFileContent(string $dir, string $file, string $content): void
    {
        $response = Http::withHeaders($this->headers())
            ->asForm()
            ->post($this->uapiUrl().'/Fileman/save_file_content', [
                'dir' => $dir,
                'file' => $file,
                'content' => $content,
            ]);

        $json = method_exists($response, 'json') ? $response->json() : [];

        $resultNode = $json['result'] ?? null;
        $status = $resultNode['status'] ?? $json['status'] ?? null;
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
    }

    public function uploadFile(string $dir, string $name, string $content): void
    {
        $response = Http::withHeaders($this->headers())
            ->asMultipart()
            ->post($this->uapiUrl().'/Fileman/upload_files', [
                [
                    'name' => 'dir',
                    'contents' => $dir,
                ],
                [
                    'name' => 'file-1',
                    'contents' => $content,
                    'filename' => $name,
                ],
            ]);

        $json = method_exists($response, 'json') ? $response->json() : [];

        $resultNode = $json['result'] ?? null;

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
    }

    public function deleteFile(string $dir, string $file): void
    {
        $this->call('Fileman', 'fileop', [
            'op' => 'trash',
            'sourcefiles' => $dir.'/'.$file,
        ]);
    }

    public function renameFile(string $dir, string $oldName, string $newName): void
    {
        $this->call('Fileman', 'fileop', [
            'op' => 'rename',
            'sourcefiles' => $dir.'/'.$oldName,
            'destfiles' => $dir.'/'.$newName,
        ]);
    }

    public function copyFile(string $dir, string $source, string $dest): void
    {
        $content = $this->getFileContent($dir, $source);

        if ($content !== null) {
            $this->saveFileContent($dir, $dest, $content);
        }
    }

    public function moveFile(string $dir, string $source, string $dest): void
    {
        $content = $this->getFileContent($dir, $source);

        if ($content !== null) {
            $this->saveFileContent($dir, $dest, $content);
            $this->deleteFile($dir, $source);
        }
    }

    public function directoryExists(string $dir): bool
    {
        try {
            $files = $this->listFiles(dirname($dir));
            $name = basename($dir);

            foreach ($files as $file) {
                if ($file['file'] === $name && $file['type'] === 'dir') {
                    return true;
                }
            }

            return false;
        } catch (\Exception) {
            return false;
        }
    }

    public function userBaseDir(string $tenantDomain, int $userId): string
    {
        return "/public_html/cpanel_files/{$tenantDomain}/users/{$userId}";
    }

    public function ensureUserDir(string $tenantDomain, int $userId): string
    {
        try {
            $this->call('Fileman', 'mkdir', [
                'path' => '/public_html',
                'name' => 'cpanel_files',
            ]);
        } catch (\Exception) {
        }

        try {
            $this->call('Fileman', 'mkdir', [
                'path' => '/public_html/cpanel_files',
                'name' => $tenantDomain,
            ]);
        } catch (\Exception) {
        }

        try {
            $this->call('Fileman', 'mkdir', [
                'path' => '/public_html/cpanel_files/'.$tenantDomain,
                'name' => 'users',
            ]);
        } catch (\Exception) {
        }

        try {
            $this->call('Fileman', 'mkdir', [
                'path' => '/public_html/cpanel_files/'.$tenantDomain.'/users',
                'name' => (string) $userId,
            ]);
        } catch (\Exception) {
        }

        return $this->userBaseDir($tenantDomain, $userId);
    }
}
