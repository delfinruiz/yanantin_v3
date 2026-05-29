<?php

namespace App\Http\Controllers;

use App\Models\FileItem;
use App\Models\FileShareLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class OnlyOfficeController extends Controller
{
    public function downloadInternal(Request $request, FileItem $fileItem)
    {
        if (! $request->hasValidSignature()) {
            Log::channel('daily')->warning('[OnlyOffice downloadInternal] Invalid signature', [
                'url' => $request->fullUrl(),
                'fileItem_id' => $fileItem->id,
            ]);
            abort(403);
        }

        $name = $fileItem->filename ?? $fileItem->name;
        $tenantId = tenant()->id;
        $path = trim(
            "tenants/{$tenantId}/files/{$fileItem->user_id}/".trim($fileItem->path, '/').'/'.$name,
            '/'
        );

        $exists = Storage::disk('public')->exists($path);
        $absolute = Storage::disk('public')->path($path);
        $url = Storage::disk('public')->url($path);

        Log::channel('daily')->info('[OnlyOffice downloadInternal]', [
            'fileItem_id' => $fileItem->id,
            'tenant' => $tenantId,
            'user_id' => $fileItem->user_id,
            'name' => $name,
            'path' => $path,
            'exists' => $exists,
            'absolute' => $absolute,
            'public_url' => $url,
            'file_exists' => $exists && file_exists($absolute),
        ]);

        if (! $exists) {
            abort(404);
        }

        return response()->file($absolute);
    }

    public function openPublic($token, Request $request)
    {
        $link = FileShareLink::with('fileItem')->where('token', $token)->firstOrFail();

        if (! $link->isValid()) {
            abort(404, 'El enlace ha expirado o no es valido.');
        }

        $fileItem = $link->fileItem;
        $targetFile = $fileItem;

        if ($fileItem->is_folder) {
            $relativePath = $request->query('path', '');
            $relativePath = trim($relativePath, '/');

            if ($relativePath === '') {
                abort(400, 'Se requiere especificar un archivo dentro de la carpeta.');
            }

            $rootLogicalPath = trim($fileItem->path.$fileItem->name, '/');
            $targetLogicalPath = trim($rootLogicalPath.'/'.dirname($relativePath), '/');

            if (dirname($relativePath) === '.') {
                $targetLogicalPath = $rootLogicalPath;
            }

            $fileName = basename($relativePath);

            $targetFile = FileItem::where('user_id', $fileItem->user_id)
                ->where('path', $this->normalizePath('/'.$targetLogicalPath))
                ->where('name', $fileName)
                ->where('is_folder', false)
                ->firstOrFail();
        }

        $permission = $link->permission;
        $isEditable = ($permission === 'edit');
        $tenantId = tenant()->id;
        $path = trim(
            "tenants/{$tenantId}/files/{$targetFile->user_id}/".trim($targetFile->path, '/').'/'.$targetFile->name,
            '/'
        );

        if (! Storage::disk('public')->exists($path)) {
            abort(404, 'Archivo no encontrado');
        }

        $absolutePath = Storage::disk('public')->path($path);

        if (! file_exists($absolutePath)) {
            abort(404, 'Archivo fisico no encontrado: '.$absolutePath);
        }

        $docKey = md5($tenantId.$path.filemtime($absolutePath));
        $this->registerKeyMap($docKey, $path, $tenantId);

        $publicDownloadUrl = route('public.download.onlyoffice', ['token' => $token]);

        Log::channel('daily')->info('[OnlyOffice openPublic] Generating editor config', [
            'token' => $token,
            'tenant' => $tenantId,
            'targetFile_id' => $targetFile->id,
            'name' => $targetFile->name,
            'disk_path' => $path,
            'disk_exists' => Storage::disk('public')->exists($path),
            'download_url' => $publicDownloadUrl,
            'callback_url' => route('onlyoffice.callback'),
            'is_editable' => $isEditable,
        ]);

        $config = [
            'document' => [
                'fileType' => pathinfo($targetFile->name, PATHINFO_EXTENSION),
                'key' => $docKey,
                'title' => $targetFile->name,
                'url' => route('public.download.onlyoffice', ['token' => $token]),
                'permissions' => [
                    'edit' => $isEditable,
                    'download' => true,
                    'print' => true,
                    'review' => $isEditable,
                ],
            ],
            'editorConfig' => [
                'callbackUrl' => route('onlyoffice.callback'),
                'lang' => 'es',
                'locale' => 'es',
                'region' => 'es-ES',
                'mode' => $isEditable ? 'edit' : 'view',
                'user' => [
                    'id' => 'guest-'.uniqid(),
                    'name' => 'Invitado',
                ],
            ],
            'documentType' => $this->getDocumentType(pathinfo($targetFile->name, PATHINFO_EXTENSION)),
        ];

        return view('onlyoffice.editor', compact('config'));
    }

    public function open(FileItem $fileItem)
    {
        $user = Auth::user();

        if ($fileItem->user_id === $user->id) {
            $permission = 'full';
        } else {
            $permission = $fileItem->sharedWith()
                ->where('users.id', $user->id)
                ->value('permission');

            abort_if(! $permission, 403);
        }

        $isEditable = in_array($permission, ['full', 'edit']);
        $tenantId = tenant()->id;

        $path = trim(
            "tenants/{$tenantId}/files/{$fileItem->user_id}/".trim($fileItem->path, '/').'/'.$fileItem->name,
            '/'
        );

        if (! Storage::disk('public')->exists($path)) {
            abort(404, 'Archivo no encontrado');
        }

        $absolutePath = Storage::disk('public')->path($path);

        if (! file_exists($absolutePath)) {
            abort(404, 'Archivo no accesible en disco');
        }

        $docKey = md5($tenantId.$path.filemtime($absolutePath));

        $mapPath = storage_path("app/tenants/{$tenantId}/onlyoffice/key_map.json");

        if (! is_dir(dirname($mapPath))) {
            mkdir(dirname($mapPath), 0755, true);
        }

        $keyMap = [];
        if (file_exists($mapPath)) {
            $keyMap = json_decode(file_get_contents($mapPath), true) ?? [];
        }

        $keyMap[$docKey] = $path;
        file_put_contents($mapPath, json_encode($keyMap, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $signedUrl = URL::temporarySignedRoute(
            'onlyoffice.download.internal',
            now()->addMinutes(60),
            ['fileItem' => $fileItem->id]
        );

        Log::channel('daily')->info('[OnlyOffice open] Generating editor config', [
            'fileItem_id' => $fileItem->id,
            'tenant' => $tenantId,
            'user_id' => $fileItem->user_id,
            'name' => $fileItem->name,
            'disk_path' => $path,
            'disk_exists' => Storage::disk('public')->exists($path),
            'signed_url' => $signedUrl,
            'callback_url' => route('onlyoffice.callback'),
            'is_editable' => $isEditable,
        ]);

        $config = [
            'document' => [
                'fileType' => pathinfo($fileItem->name, PATHINFO_EXTENSION),
                'key' => $docKey,
                'title' => $fileItem->name,
                'url' => $signedUrl,
                'permissions' => [
                    'edit' => $isEditable,
                    'download' => true,
                    'print' => true,
                    'review' => $isEditable,
                ],
            ],
            'editorConfig' => [
                'callbackUrl' => route('onlyoffice.callback'),
                'lang' => 'es',
                'locale' => 'es',
                'region' => 'es-ES',
                'mode' => $isEditable ? 'edit' : 'view',
                'user' => [
                    'id' => (string) $user->id,
                    'name' => $user->name,
                ],
            ],
            'documentType' => $this->getDocumentType(pathinfo($fileItem->name, PATHINFO_EXTENSION)),
        ];

        return view('onlyoffice.editor', compact('config'));
    }

    public function downloadForOnlyOffice($token)
    {
        $link = FileShareLink::with('fileItem')->where('token', $token)->firstOrFail();

        if (! $link->isValid()) {
            Log::channel('daily')->warning('[OnlyOffice downloadForOnlyOffice] Link expired', ['token' => $token]);
            abort(404);
        }

        $fileItem = $link->fileItem;
        $tenantId = tenant()->id;
        $path = trim(
            "tenants/{$tenantId}/files/{$fileItem->user_id}/".trim($fileItem->path, '/').'/'.$fileItem->name,
            '/'
        );

        $exists = Storage::disk('public')->exists($path);

        Log::channel('daily')->info('[OnlyOffice downloadForOnlyOffice]', [
            'token' => $token,
            'tenant' => $tenantId,
            'fileItem_id' => $fileItem->id,
            'name' => $fileItem->name,
            'path' => $path,
            'exists' => $exists,
        ]);

        if (! $exists) {
            abort(404);
        }

        return response()->file(Storage::disk('public')->path($path));
    }

    private function registerKeyMap($docKey, $path, string $tenantId): void
    {
        $mapPath = storage_path("app/tenants/{$tenantId}/onlyoffice/key_map.json");

        if (! is_dir(dirname($mapPath))) {
            mkdir(dirname($mapPath), 0755, true);
        }

        $keyMap = [];
        if (file_exists($mapPath)) {
            $keyMap = json_decode(file_get_contents($mapPath), true) ?? [];
        }

        $keyMap[$docKey] = $path;
        file_put_contents($mapPath, json_encode($keyMap, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function normalizePath(string $path): string
    {
        if ($path === '/' || $path === '') {
            return '/';
        }

        if (! str_starts_with($path, '/')) {
            $path = '/'.$path;
        }

        if (! str_ends_with($path, '/')) {
            $path = $path.'/';
        }

        return $path;
    }

    private function getDocumentType($ext)
    {
        return match (strtolower($ext)) {
            'docx', 'doc', 'txt', 'odt', 'rtf', 'html', 'htm' => 'word',
            'xlsx', 'xls', 'ods', 'csv' => 'cell',
            'pptx', 'ppt', 'odp' => 'slide',
            'pdf' => 'pdf',
            default => 'word',
        };
    }
}
