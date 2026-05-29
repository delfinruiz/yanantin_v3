<?php

namespace App\Http\Controllers;

use App\Models\FileItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OnlyOfficeCallbackController extends Controller
{
    public function handle(Request $request)
    {
        Log::channel('daily')->info('[OnlyOffice callback] Recibido', [
            'status' => $request->input('status'),
            'key' => $request->input('key'),
            'url' => $request->input('url'),
            'tenant' => tenant()->id,
            'all' => $request->all(),
        ]);

        $data = $request->all();

        $status = $data['status'] ?? null;
        $fileUrl = $data['url'] ?? null;
        $documentKey = $data['key'] ?? null;

        if (! in_array($status, [2, 6]) || ! $fileUrl || ! $documentKey) {
            Log::channel('daily')->info('[OnlyOffice callback] Sin cambios que guardar', ['status' => $status]);

            return response()->json(['error' => 0]);
        }

        $tenantId = tenant()->id;
        $keyMapPath = storage_path("app/tenants/{$tenantId}/onlyoffice/key_map.json");

        if (! file_exists($keyMapPath)) {
            Log::channel('daily')->error('[ONLYOFFICE] key_map.json no existe: '.$keyMapPath);

            return response()->json(['error' => 1]);
        }

        $keyMap = json_decode(file_get_contents($keyMapPath), true);

        if (! isset($keyMap[$documentKey])) {
            Log::channel('daily')->error('[ONLYOFFICE] Clave no encontrada en mapa', [
                'key' => $documentKey,
                'keys_count' => count($keyMap),
            ]);

            return response()->json(['error' => 1]);
        }

        $relativePath = ltrim(str_replace('//', '/', $keyMap[$documentKey]), '/');

        Log::channel('daily')->info('[ONLYOFFICE] Ruta destino', ['path' => $relativePath]);

        $disk = Storage::disk('public');

        $fileContent = $this->downloadFromOnlyOffice($fileUrl);

        if ($fileContent === false || strlen($fileContent) === 0) {
            Log::channel('daily')->error('[ONLYOFFICE] Descarga fallida', ['url' => $fileUrl]);

            return response()->json(['error' => 1]);
        }

        $directory = dirname($relativePath);

        if (! $disk->exists($directory)) {
            $disk->makeDirectory($directory);
        }

        if (! $disk->put($relativePath, $fileContent)) {
            Log::channel('daily')->error('[ONLYOFFICE] Error escribiendo archivo', ['path' => $relativePath]);

            return response()->json(['error' => 1]);
        }

        Log::channel('daily')->info('[ONLYOFFICE] Archivo guardado', [
            'path' => $relativePath,
            'size' => strlen($fileContent),
        ]);

        try {
            // El path es: tenants/{tenantId}/files/{userId}/docs/reporte.docx
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

                    Log::channel('daily')->info('[ONLYOFFICE] BD sincronizada', [
                        'id' => $fileItem->id,
                        'new_size' => strlen($fileContent),
                    ]);
                } else {
                    Log::channel('daily')->warning('[ONLYOFFICE] No se encontro registro en BD');
                }
            }
        } catch (\Exception $e) {
            Log::error('OnlyOffice: Error sincronizando BD', ['error' => $e->getMessage()]);
        }

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

            Log::error('OnlyOffice: Download failed', [
                'url' => $url,
                'status' => $response->status(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('OnlyOffice: Download exception', [
                'url' => $url,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
