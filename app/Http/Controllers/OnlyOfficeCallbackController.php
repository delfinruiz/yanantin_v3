<?php

namespace App\Http\Controllers;

use App\Models\FileItem;
use App\Services\CPanelFilemanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OnlyOfficeCallbackController extends Controller
{
    public function handle(Request $request)
    {
        try {
            return $this->processCallback($request);
        } catch (\Throwable $e) {
            Log::channel('daily')->error('[OnlyOffice callback] EXCEPCION', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 1]);
        }
    }

    private function processCallback(Request $request)
    {
        $rawInput = $request->getContent();

        Log::channel('daily')->info('[OnlyOffice callback] POST recibido', [
            'tenant_id' => tenant()?->id ?? 'NULL',
            'subdomain' => $request->getHost(),
            'ip' => $request->ip(),
            'content_type' => $request->header('Content-Type'),
            'user_agent' => $request->header('User-Agent'),
            'body' => mb_substr($rawInput, 0, 2000),
        ]);

        $data = $request->all();

        $status = $data['status'] ?? null;
        $fileUrl = $data['url'] ?? null;
        $documentKey = $data['key'] ?? null;

        Log::channel('daily')->info('[OnlyOffice callback] Datos extraidos', [
            'status' => $status,
            'has_url' => ! empty($fileUrl),
            'has_key' => ! empty($documentKey),
        ]);

        if (! in_array($status, [2, 6]) || ! $fileUrl || ! $documentKey) {
            Log::channel('daily')->info('[OnlyOffice callback] Sin cambios que guardar', ['status' => $status]);

            return response()->json(['error' => 0]);
        }

        $tenantId = tenant()?->id;

        if (! $tenantId) {
            Log::channel('daily')->error('[OnlyOffice callback] SIN TENANT - subdomain no resuelto', [
                'host' => $request->getHost(),
            ]);

            return response()->json(['error' => 1]);
        }

        $keyMapPath = storage_path("app/tenants/{$tenantId}/onlyoffice/key_map.json");

        Log::channel('daily')->info('[OnlyOffice callback] Buscando key_map', [
            'tenantId' => $tenantId,
            'map_path' => $keyMapPath,
            'map_exists' => file_exists($keyMapPath),
            'document_key' => $documentKey,
        ]);

        if (! file_exists($keyMapPath)) {
            Log::channel('daily')->error('[OnlyOffice callback] key_map.json no existe', [
                'path' => $keyMapPath,
                'tenant_id' => $tenantId,
            ]);

            return response()->json(['error' => 1]);
        }

        $keyMap = json_decode(file_get_contents($keyMapPath), true);

        if (! isset($keyMap[$documentKey])) {
            Log::channel('daily')->error('[OnlyOffice callback] Clave no encontrada en key_map', [
                'document_key' => $documentKey,
                'keys_en_mapa' => array_keys($keyMap),
                'total_keys' => count($keyMap),
            ]);

            return response()->json(['error' => 1]);
        }

        $relativePath = ltrim(str_replace('//', '/', $keyMap[$documentKey]), '/');

        if (str_starts_with($relativePath, 'cpanel://')) {
            return $this->handleCpanelCallback(
                substr($relativePath, 8),
                $fileUrl,
                $tenantId,
            );
        }

        Log::channel('daily')->info('[OnlyOffice callback] Ruta resuelta', [
            'relative_path' => $relativePath,
            'absoluta' => Storage::disk('public')->path($relativePath),
        ]);

        $disk = Storage::disk('public');

        Log::channel('daily')->info('[OnlyOffice callback] Descargando desde OnlyOffice', ['url' => $fileUrl]);

        $fileContent = $this->downloadFromOnlyOffice($fileUrl);

        if ($fileContent === false || strlen($fileContent) === 0) {
            Log::channel('daily')->error('[OnlyOffice callback] Descarga fallida', [
                'url' => $fileUrl,
                'size' => $fileContent === false ? 'false' : strlen($fileContent),
            ]);

            return response()->json(['error' => 1]);
        }

        Log::channel('daily')->info('[OnlyOffice callback] Archivo descargado', ['size' => strlen($fileContent)]);

        $directory = dirname($relativePath);

        if (! $disk->exists($directory)) {
            $disk->makeDirectory($directory);
            Log::channel('daily')->info('[OnlyOffice callback] Directorio creado', ['dir' => $directory]);
        }

        if (! $disk->put($relativePath, $fileContent)) {
            Log::channel('daily')->error('[OnlyOffice callback] Error escribiendo archivo en disco', [
                'path' => $relativePath,
            ]);

            return response()->json(['error' => 1]);
        }

        Log::channel('daily')->info('[OnlyOffice callback] Archivo guardado en disco', [
            'path' => $relativePath,
            'size' => strlen($fileContent),
        ]);

        try {
            $parts = explode('/', $relativePath);

            if (count($parts) >= 5 && $parts[0] === 'tenants' && $parts[2] === 'files') {
                $userId = (int) $parts[3];
                $fileName = array_pop($parts);

                $logicalPath = '/';
                if (count($parts) > 4) {
                    $logicalPath = '/'.implode('/', array_slice($parts, 4)).'/';
                }

                $query = FileItem::where('user_id', $userId)
                    ->where('name', $fileName);

                if ($logicalPath === '/') {
                    $query->where(function ($q) {
                        $q->where('path', '/')
                            ->orWhere('path', '')
                            ->orWhereNull('path');
                    });
                } else {
                    $query->where('path', $logicalPath);
                }

                $fileItem = $query->first();

                if ($fileItem) {
                    $fileItem->update([
                        'size' => strlen($fileContent),
                        'mime_type' => mime_content_type($disk->path($relativePath)) ?: 'application/octet-stream',
                    ]);

                    Log::channel('daily')->info('[OnlyOffice callback] BD sincronizada', [
                        'fileItem_id' => $fileItem->id,
                        'new_size' => strlen($fileContent),
                    ]);
                } else {
                    Log::channel('daily')->warning('[OnlyOffice callback] No se encontro registro en BD', [
                        'user_id' => $userId,
                        'path' => $logicalPath,
                        'name' => $fileName,
                    ]);
                }
            } else {
                Log::channel('daily')->warning('[OnlyOffice callback] Path no coincide con formato esperado', [
                    'path' => $relativePath,
                    'parts_count' => count($parts),
                ]);
            }
        } catch (\Exception $e) {
            Log::channel('daily')->error('[OnlyOffice callback] Error sincronizando BD', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        Log::channel('daily')->info('[OnlyOffice callback] COMPLETADO EXITOSAMENTE');

        return response()->json(['error' => 0]);
    }

    private function downloadFromOnlyOffice(string $url): string|false
    {
        try {
            $response = Http::withOptions([
                'verify' => false,
                'connect_timeout' => 15,
                'timeout' => 120,
            ])
                ->withUserAgent('Laravel-OnlyOffice')
                ->get($url);

            if ($response->successful()) {
                return $response->body();
            }

            Log::channel('daily')->error('[OnlyOffice callback] HTTP download failed', [
                'url' => $url,
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 500),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::channel('daily')->error('[OnlyOffice callback] Download exception', [
                'url' => $url,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function handleCpanelCallback(string $docKey, string $fileUrl, string $tenantId): JsonResponse
    {
        $cpanelDir = storage_path("app/tenants/{$tenantId}/onlyoffice/cpanel");
        $metaPath = "{$cpanelDir}/{$docKey}.meta.json";
        $dataPath = "{$cpanelDir}/{$docKey}.dat";

        if (! file_exists($metaPath)) {
            Log::channel('daily')->error('[OnlyOffice callback cPanel] Meta no encontrada', [
                'docKey' => $docKey,
                'metaPath' => $metaPath,
            ]);

            return response()->json(['error' => 1]);
        }

        $meta = json_decode(file_get_contents($metaPath), true);

        $fileContent = $this->downloadFromOnlyOffice($fileUrl);

        if ($fileContent === false || strlen($fileContent) === 0) {
            Log::channel('daily')->error('[OnlyOffice callback cPanel] Descarga fallida', [
                'docKey' => $docKey,
                'url' => $fileUrl,
            ]);

            return response()->json(['error' => 1]);
        }

        file_put_contents($dataPath, $fileContent);

        try {
            $service = app(CPanelFilemanService::class);
            $service->saveFileContent($meta['dir'], $meta['name'], $fileContent);

            Log::channel('daily')->info('[OnlyOffice callback cPanel] Archivo subido a cPanel', [
                'dir' => $meta['dir'],
                'name' => $meta['name'],
                'size' => strlen($fileContent),
            ]);
        } catch (\Exception $e) {
            Log::channel('daily')->error('[OnlyOffice callback cPanel] Error subiendo a cPanel', [
                'dir' => $meta['dir'],
                'name' => $meta['name'],
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 1]);
        }

        @unlink($dataPath);
        @unlink($metaPath);

        return response()->json(['error' => 0]);
    }
}
