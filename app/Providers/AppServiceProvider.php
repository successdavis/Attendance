<?php

namespace App\Providers;

use App\Services\AttendanceSettingsService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register AttendanceSettingsService as a singleton so that the settings
        // cache is shared across the entire request lifecycle.
        $this->app->singleton(AttendanceSettingsService::class);
    }

    public function boot(): void
    {
        $this->configureRateLimiters();
    }

    private function configureRateLimiters(): void
    {
        // Device API rate limiter — keyed by device ID (after auth) or IP (before auth)
        // 120 requests per minute is generous for real-time scan terminals
        RateLimiter::for('device-api', function (Request $request) {
            $key = $request->device?->id ?? $request->ip();

            return Limit::perMinute(120)->by($key);
        });
    }
}
