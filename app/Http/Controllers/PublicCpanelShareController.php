<?php

namespace App\Http\Controllers;

use App\Models\CpanelFileShareLink;
use App\Services\CPanelFilemanService;

class PublicCpanelShareController extends Controller
{
    public function show(string $token)
    {
        $link = CpanelFileShareLink::where('token', $token)->firstOrFail();

        if (! $link->isValid()) {
            abort(404, 'El enlace ha expirado o no es valido.');
        }

        $ext = strtolower(pathinfo($link->name, PATHINFO_EXTENSION));
        $isOffice = in_array($ext, ['docx', 'doc', 'xlsx', 'xls', 'pptx', 'ppt', 'pdf', 'txt', 'csv', 'odt', 'ods', 'odp', 'rtf']);

        $fileSize = 0;

        try {
            $service = app(CPanelFilemanService::class);
            $files = $service->listFiles($link->path);

            foreach ($files as $f) {
                if (($f['file'] ?? '') === $link->name) {
                    $fileSize = (int) ($f['size'] ?? 0);

                    break;
                }
            }
        } catch (\Exception) {
        }

        return view('public.cpanel-share', [
            'link' => $link,
            'name' => $link->name,
            'size' => $fileSize,
            'isOffice' => $isOffice,
            'ownerName' => $link->owner?->name ?? 'Usuario',
            'expiresAt' => $link->expires_at?->format('d/m/Y') ?? 'Nunca',
        ]);
    }

    public function download(string $token)
    {
        $link = CpanelFileShareLink::where('token', $token)->firstOrFail();

        if (! $link->isValid()) {
            abort(404, 'El enlace ha expirado o no es valido.');
        }

        $link->increment('downloads');

        try {
            $service = app(CPanelFilemanService::class);
            $content = $service->getFileContent($link->path, $link->name);
        } catch (\Exception) {
            abort(500, 'Error al leer el archivo desde cPanel.');
        }

        if ($content === null) {
            abort(404, 'El archivo no pudo ser leido.');
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'cpanel_public_');
        file_put_contents($tempPath, $content);

        return response()->download($tempPath, $link->name)->deleteFileAfterSend(true);
    }
}
