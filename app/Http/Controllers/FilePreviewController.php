<?php

namespace App\Http\Controllers;

use App\Models\FileItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class FilePreviewController extends Controller
{
    public function show(FileItem $fileItem)
    {
        if ($fileItem->user_id !== Auth::id()) {
            $share = $fileItem->sharedWith()
                ->where('users.id', Auth::id())
                ->first();

            if (! $share) {
                abort(403, 'No tienes permiso para ver este archivo.');
            }

            $pivot = $share->pivot;
            if (($pivot->requires_ack ?? false) && empty($pivot->ack_completed_at)) {
                abort(403, 'Debes completar la toma de conocimiento antes de acceder.');
            }
        }

        $name = $fileItem->filename ?? $fileItem->name;
        $tenantId = tenant()->id;

        $path = trim(
            "tenants/{$tenantId}/files/{$fileItem->user_id}/".trim($fileItem->path, '/').'/'.$name,
            '/'
        );

        if (! Storage::disk('public')->exists($path)) {
            abort(404, 'Archivo no encontrado en el almacenamiento.');
        }

        return response()->file(Storage::disk('public')->path($path));
    }
}
