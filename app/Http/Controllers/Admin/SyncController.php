<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StudentSyncLog;
use App\Services\AttendanceSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SyncController extends Controller
{
    public function index(AttendanceSettingsService $settings): Response
    {
        $lastSync = StudentSyncLog::latest('started_at')->first();

        $config = [
            'sync_api_url'              => $settings->getString('sync_api_url'),
            'sync_schedule_enabled'     => $settings->getBool('sync_schedule_enabled'),
            'sync_schedule_cron'        => $settings->getString('sync_schedule_cron'),
            'sync_deactivate_missing'   => $settings->getBool('sync_deactivate_missing'),
            'program_expiry_enforcement' => $settings->getBool('program_expiry_enforcement'),
        ];

        return Inertia::render('Admin/Sync/Index', compact('lastSync', 'config'));
    }

    public function run(Request $request): RedirectResponse
    {
        $apiUrl = app(AttendanceSettingsService::class)->getString('sync_api_url');

        if (empty($apiUrl)) {
            return back()->withErrors(['sync' => 'Sync API URL is not configured. Go to Settings to add it.']);
        }

        // TODO Phase 6: dispatch SyncStudentsJob::class
        // \App\Jobs\SyncStudentsJob::dispatch('manual', $request->user()->id);

        return back()->with('success', 'Student sync queued. Check the logs for progress.');
    }

    public function logs(): Response
    {
        $logs = StudentSyncLog::with('triggeredByUser')
            ->orderByDesc('started_at')
            ->paginate(25);

        return Inertia::render('Admin/Sync/Logs', compact('logs'));
    }
}
