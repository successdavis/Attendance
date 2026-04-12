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

Route::middleware(['auth'])->group(function () {
    // Scan page UI
    Route::get('/attendance/scan', [AttendanceController::class, 'show'])
         ->name('attendance.scan.page');

    // API endpoint that processes each scan
    Route::post('/attendance/scan', [AttendanceController::class, 'scan'])
         ->name('attendance.scan');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
