<?php

declare(strict_types=1);

use App\Http\Controllers\FilePreviewController;
use App\Http\Controllers\OnlyOfficeCallbackController;
use App\Http\Controllers\OnlyOfficeController;
use App\Http\Controllers\PublicShareController;
use App\Http\Controllers\TenantLandingController;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::middleware([
    'web',
    InitializeTenancyBySubdomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    Route::get('/', [TenantLandingController::class, 'show']);

    // File Manager - Public routes
    Route::get('/s/{token}', [PublicShareController::class, 'show'])->name('public.share');
    Route::get('/d/{token}', [PublicShareController::class, 'download'])->name('public.download');
    Route::get('/o/{token}', [OnlyOfficeController::class, 'openPublic'])->name('public.onlyoffice');
    Route::get('/o/download/{token}', [OnlyOfficeController::class, 'downloadForOnlyOffice'])->name('public.download.onlyoffice');

    // OnlyOffice callback
    Route::post('/onlyoffice/callback', [OnlyOfficeCallbackController::class, 'handle'])->name('onlyoffice.callback');

    // File Manager - Auth required
    Route::middleware(['auth'])->group(function () {
        Route::get('/onlyoffice/open/{fileItem}', [OnlyOfficeController::class, 'open'])->name('onlyoffice.open');
        Route::get('/file/preview/{fileItem}', [FilePreviewController::class, 'show'])->name('file.preview');
    });

    // Signed URL
    Route::get('/onlyoffice/download-internal/{fileItem}', [OnlyOfficeController::class, 'downloadInternal'])
        ->middleware('signed')
        ->name('onlyoffice.download.internal');
});
