<?php

use App\Http\Controllers\AttendanceController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// ─── Attendance scan (web UI / kiosk) ─────────────────────────────────────────

Route::middleware(['auth'])->group(function () {
    Route::get('/attendance/scan', [AttendanceController::class, 'showScan'])
         ->name('attendance.scan.page');

    Route::post('/attendance/scan', [AttendanceController::class, 'scan'])
         ->name('attendance.scan');

    Route::get('/attendance/today', [AttendanceController::class, 'today'])
         ->name('attendance.today');
});

// ─── Admin panel ──────────────────────────────────────────────────────────────
require __DIR__.'/admin.php';

// ─── Settings and auth (Laravel starter kit) ──────────────────────────────────
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
