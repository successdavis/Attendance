<?php

use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\CorrectionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DeviceController;
use App\Http\Controllers\Admin\OverrideController;
use App\Http\Controllers\Admin\PolicyController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SyncController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserCredentialController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| All admin routes require the user to be authenticated and to hold the
| 'admin' Spatie role. Individual controller actions are further protected
| by granular 'permission' middleware where applicable.
|
*/

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:admin'])
    ->group(function () {

        // ─── Dashboard ───────────────────────────────────────────────────────
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // ─── User management ─────────────────────────────────────────────────
        Route::resource('users', UserController::class)->except(['show']);
        Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::post('users/{user}/status', [UserController::class, 'updateStatus'])
             ->name('users.status');

        // Biometric / RFID credentials per user
        Route::prefix('users/{user}/credentials')
             ->name('users.credentials.')
             ->group(function () {
                 Route::get('/',                                   [UserCredentialController::class, 'index'])->name('index');
                 Route::post('/',                                  [UserCredentialController::class, 'store'])->name('store');
                 Route::delete('{credential}',                     [UserCredentialController::class, 'destroy'])->name('destroy');
             });

        // ─── Device management ───────────────────────────────────────────────
        Route::resource('devices', DeviceController::class)->except(['show']);
        Route::post('devices/{device}/token', [DeviceController::class, 'rotateToken'])
             ->name('devices.rotate-token');

        // ─── Attendance settings ─────────────────────────────────────────────
        Route::get('settings',              [SettingsController::class, 'index'])->name('settings.index');
        Route::patch('settings',            [SettingsController::class, 'update'])->name('settings.update');

        // Daily schedule overrides
        Route::prefix('settings/overrides')->name('settings.overrides.')->group(function () {
            Route::get('/',           [OverrideController::class, 'index'])->name('index');
            Route::post('/',          [OverrideController::class, 'store'])->name('store');
            Route::patch('{date}',    [OverrideController::class, 'update'])->name('update');
            Route::delete('{date}',   [OverrideController::class, 'destroy'])->name('destroy');
        });

        // ─── Attendance policies ─────────────────────────────────────────────
        Route::resource('policies', PolicyController::class)->except(['show', 'create', 'edit']);

        // ─── Reports ─────────────────────────────────────────────────────────
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/daily',        [ReportController::class, 'daily'])->name('daily');
            Route::get('/range',        [ReportController::class, 'range'])->name('range');
            Route::get('/user/{user}',  [ReportController::class, 'user'])->name('user');
            Route::post('/export',      [ReportController::class, 'export'])->name('export');
        });

        // ─── Manual attendance corrections ───────────────────────────────────
        Route::prefix('corrections')->name('corrections.')->group(function () {
            Route::get('/',             [CorrectionController::class, 'index'])->name('index');
            Route::post('/',            [CorrectionController::class, 'store'])->name('store');
            Route::patch('{log}',       [CorrectionController::class, 'update'])->name('update');
            Route::delete('{log}',      [CorrectionController::class, 'destroy'])->name('destroy');
        });

        // ─── Student sync ────────────────────────────────────────────────────
        Route::prefix('sync')->name('sync.')->group(function () {
            Route::get('/',             [SyncController::class, 'index'])->name('index');
            Route::post('/run',         [SyncController::class, 'run'])->name('run');
            Route::get('/logs',         [SyncController::class, 'logs'])->name('logs');
        });

        // ─── Audit log ───────────────────────────────────────────────────────
        Route::get('audit', [AuditController::class, 'index'])->name('audit.index');
    });
