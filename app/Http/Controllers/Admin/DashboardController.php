<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceDevice;
use App\Models\AttendanceLog;
use App\Models\StudentSyncLog;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $today = now()->toDateString();

        $stats = [
            'totalUsers'      => User::whereIn('role', ['staff', 'student'])->count(),
            'activeUsers'     => User::where('status', 'active')->count(),
            'todayScans'      => AttendanceLog::whereDate('logged_at', $today)->count(),
            'todayPresent'    => AttendanceLog::whereDate('logged_at', $today)
                                    ->where('status', 'check_in')
                                    ->distinct('user_id')
                                    ->count(),
            'activeDevices'   => AttendanceDevice::where('status', 'active')->count(),
            'lastSync'        => StudentSyncLog::where('status', 'completed')
                                    ->latest('finished_at')
                                    ->value('finished_at'),
        ];

        $recentLogs = AttendanceLog::with('user')
            ->whereDate('logged_at', $today)
            ->latest('logged_at')
            ->limit(10)
            ->get()
            ->map(fn ($log) => [
                'id'        => $log->id,
                'userName'  => $log->user?->name,
                'userRole'  => $log->user?->role,
                'method'    => $log->method,
                'status'    => $log->status,
                'loggedAt'  => $log->logged_at->toDateTimeString(),
            ]);

        return Inertia::render('Admin/Dashboard', compact('stats', 'recentLogs'));
    }
}
