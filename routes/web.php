<?php

use App\Http\Controllers\MeetRoomController;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain;

foreach (config('tenancy.central_domains') as $domain) {
    Route::domain($domain)->group(function () {
        Route::get('/', function () {
            return view('central.landing');
        });
    });
}

Route::prefix('meet')
    ->name('meet.')
    ->middleware([InitializeTenancyBySubdomain::class])
    ->group(function () {
        Route::get('/{roomCode}/join', [MeetRoomController::class, 'join'])->name('join');
        Route::get('/{roomCode}/decline/{token}', [MeetRoomController::class, 'decline'])->name('decline');
        Route::get('/{roomCode}/waiting', [MeetRoomController::class, 'waitingRoom'])->name('waiting');
        Route::get('/{roomCode}/leave', [MeetRoomController::class, 'leave'])->name('leave');
    });

Route::prefix('meet/api')
    ->name('meet.api.')
    ->middleware(['auth', InitializeTenancyBySubdomain::class])
    ->group(function () {
        Route::get('/{roomCode}/status', [MeetRoomController::class, 'apiStatus'])->name('status');
        Route::post('/{roomCode}/participant-joined', [MeetRoomController::class, 'apiParticipantJoined'])->name('participant-joined');
        Route::post('/{roomCode}/participant-left', [MeetRoomController::class, 'apiParticipantLeft'])->name('participant-left');
        Route::post('/{roomCode}/heartbeat', [MeetRoomController::class, 'heartbeat'])->name('heartbeat');
        Route::post('/{roomCode}/toggle-access', [MeetRoomController::class, 'apiToggleRoomAccess'])->name('toggle-access');
        Route::post('/{roomCode}/accept-participants', [MeetRoomController::class, 'apiAcceptParticipants'])->name('accept-participants');
    });
