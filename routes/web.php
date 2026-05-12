<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ContactController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ─── Marketing / public pages ─────────────────────────────────────────────────

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('/pricing', function () {
    return Inertia::render('Pricing');
})->name('pricing');

Route::get('/about', function () {
    return Inertia::render('About');
})->name('about');

Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'send'])->name('contact.send');

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

// ─── Temporary debug route — remove after confirming upload works ─────────────
Route::get('/debug/photo-test', function () {
    return response()->json([
        'app_url'         => config('app.url'),
        'storage_link_ok' => file_exists(public_path('storage')),
        'intervention_ok' => class_exists(\Intervention\Image\ImageManager::class),
        'user_count'      => \App\Models\User::count(),
        'photos_saved'    => \App\Models\User::whereNotNull('profile_photo_path')->count(),
        'users_with_photos' => \App\Models\User::whereNotNull('profile_photo_path')
            ->get(['id','name','profile_photo_path','profile_photo_url']),
    ]);
})->middleware(['auth']);

// ─── Admin panel ──────────────────────────────────────────────────────────────
require __DIR__.'/admin.php';

// ─── Settings and auth (Laravel starter kit) ──────────────────────────────────
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
