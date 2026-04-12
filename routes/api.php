<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Device\ScanController;
use App\Http\Controllers\Api\Device\HeartbeatController;
use App\Http\Controllers\Api\Device\StatusController;
use App\Http\Controllers\Api\Device\EnrollController;

/*
|--------------------------------------------------------------------------
| Device API Routes
|--------------------------------------------------------------------------
|
| These routes are consumed by physical attendance hardware (RFID terminals,
| fingerprint scanners, face cameras). Authentication is via a per-device
| Bearer token that is validated against the SHA-256 hash stored in the
| attendance_devices table.
|
| The AuthenticateDevice middleware validates the token, updates last_seen_at,
| and injects the resolved AttendanceDevice model into the request.
|
*/

Route::prefix('device')
    ->middleware(['throttle:device-api', \App\Http\Middleware\AuthenticateDevice::class])
    ->group(function () {

        // Process an attendance scan (RFID UID, fingerprint hash, face hash)
        Route::post('/scan', ScanController::class)->name('api.device.scan');

        // Device status and current server settings
        Route::get('/status', StatusController::class)->name('api.device.status');

        // Device heartbeat — keeps last_seen_at up to date
        Route::post('/heartbeat', HeartbeatController::class)->name('api.device.heartbeat');

        // Biometric / RFID enrollment — called from device UI during enrollment session
        Route::prefix('enroll')->name('api.device.enroll.')->group(function () {
            Route::post('/rfid',        [EnrollController::class, 'rfid'])->name('rfid');
            Route::post('/fingerprint', [EnrollController::class, 'fingerprint'])->name('fingerprint');
            Route::post('/face',        [EnrollController::class, 'face'])->name('face');
        });
    });
