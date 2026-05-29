<?php

namespace App\Http\Controllers;

use App\Models\FileItem;
use App\Models\FileShareLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PublicShareController extends Controller
{
    public function show(Request $request, string $token)
    {
        $link = FileShareLink::with('fileItem')->where('token', $token)->firstOrFail();

        if (! $link->isValid()) {
            abort(404, 'El enlace ha expirado o no es valido.');
        }

        $rootItem = $link->fileItem;

        if (! $rootItem->is_folder) {
            return view('public.share', [
                'link' => $link,
                'fileItem' => $rootItem,
                'isFolder' => false,
                'items' => [],
                'currentPath' => '/',
                'breadcrumbs' => [],
            ]);
        }

        $relativePath = $request->query('path', '');
        $relativePath = trim($relativePath, '/');

        $rootLogicalPath = $this->normalizePath($rootItem->path.$rootItem->name);

        if ($relativePath === '') {
            $targetPath = $rootLogicalPath;
        } else {
            $targetPath = $this->normalizePath($rootLogicalPath.$relativePath);
        }

        if (! str_starts_with($targetPath, $rootLogicalPath)) {
            abort(403, 'Acceso denegado.');
        }

        $items = FileItem::where('user_id', $rootItem->user_id)
            ->where('path', $targetPath)
            ->orderBy('is_folder', 'desc')
            ->orderBy('name', 'asc')
            ->get();

        $breadcrumbs = $this->buildBreadcrumbs($relativePath, $token);

        return view('public.share', [
            'link' => $link,
            'fileItem' => $rootItem,
            'isFolder' => true,
            'items' => $items,
            'currentPath' => $relativePath,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    public function download(Request $request, string $token)
    {
        $link = FileShareLink::with('fileItem')->where('token', $token)->firstOrFail();

        if (! $link->isValid()) {
            abort(404, 'El enlace ha expirado o no es valido.');
        }

        $link->increment('downloads');

        $rootItem = $link->fileItem;
        $disk = Storage::disk('public');

        if (! $rootItem->is_folder) {
            $physicalPath = $this->getPhysicalPath($rootItem);
            if (! $disk->exists($physicalPath)) {
                abort(404, 'Archivo no encontrado en el servidor.');
            }

            return response()->download($disk->path($physicalPath), $rootItem->name);
        }

        $relativePath = $request->query('path', '');
        $filename = $request->query('file');

        if (! $filename) {
            abort(404, 'Descarga de carpetas no implementada aun.');
        }

        $rootLogicalPath = $this->normalizePath($rootItem->path.$rootItem->name);
        $targetPath = $relativePath ? $this->normalizePath($rootLogicalPath.$relativePath) : $rootLogicalPath;

        if (! str_starts_with($targetPath, $rootLogicalPath)) {
            abort(403);
        }

        $targetItem = FileItem::where('user_id', $rootItem->user_id)
            ->where('path', $targetPath)
            ->where('name', $filename)
            ->where('is_folder', false)
            ->firstOrFail();

        $physicalPath = $this->getPhysicalPath($targetItem);

        if (! $disk->exists($physicalPath)) {
            abort(404, 'Archivo fisico no encontrado.');
        }

        return response()->download($disk->path($physicalPath), $targetItem->name);
    }

    private function normalizePath(string $path): string
    {
        $path = preg_replace('#/+#', '/', $path);

        if ($path === '' || $path === '/') {
            return '/';
        }

        return '/'.trim($path, '/').'/';
    }

    private function getPhysicalPath(FileItem $item): string
    {
        $tenantId = tenant()->id;
        $userRoot = "tenants/{$tenantId}/files/{$item->user_id}";
        $logicalPath = $this->normalizePath($item->path.$item->name);

        return trim($userRoot.'/'.trim($logicalPath, '/'), '/');
    }

    private function buildBreadcrumbs($relativePath, $token)
    {
        if (! $relativePath) {
            return [];
        }

        $parts = explode('/', $relativePath);
        $breadcrumbs = [];
        $accumulated = '';

        foreach ($parts as $part) {
            if (! $part) {
                continue;
            }
            $accumulated .= ($accumulated ? '/' : '').$part;
            $breadcrumbs[] = [
                'name' => $part,
                'url' => route('public.share', ['token' => $token, 'path' => $accumulated]),
            ];
        }

        return $breadcrumbs;
    }
}
