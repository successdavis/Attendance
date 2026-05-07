<?php

use App\Jobs\SyncStudentsJob;
use App\Services\AttendanceSettingsService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Student Sync Scheduler
|--------------------------------------------------------------------------
|
| The cron expression and enabled flag are stored in attendance_settings so
| admins can change the schedule without touching code.
|
| routes/console.php is loaded fresh on every scheduler tick (every minute),
| so reading the setting here is safe and always reflects the latest value.
|
| Default cron:  '0 6 * * *'  → daily at 06:00
|
| Admin controls:
|   Settings → Sync → Enable Scheduled Sync     (sync_schedule_enabled)
|   Settings → Sync → Sync Cron Expression      (sync_schedule_cron)
|
*/
$syncSettings = app(AttendanceSettingsService::class);
$syncCron     = $syncSettings->getString('sync_schedule_cron', '0 6 * * *') ?: '0 6 * * *';

Schedule::call(function () use ($syncSettings) {
    if (! $syncSettings->getBool('sync_schedule_enabled', false)) {
        return;
    }

    SyncStudentsJob::dispatch('schedule');
})
->cron($syncCron)
->name('students:sync-scheduled')
->withoutOverlapping(10);
