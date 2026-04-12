<?php

namespace App\Http\Controllers;

use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AttendanceController extends Controller
{
    public function __construct(private AttendanceService $attendanceService) {}

    /**
     * Page first load – includes counts and sign-in end time.
     */
    public function show()
    {
        $summary = $this->attendanceService->todaySummary();

        return Inertia::render('Attendance/Scan', array_merge($summary, [
            'signInEndTime' => $this->attendanceService->getSignInEndTime(),
            'timeRestrictionEnabled' => $this->attendanceService->isTimeRestrictionEnabled(), // bool
            'serverNow' => now()->toDateTimeString(), // helpful to sync client/server time
        ]));
    }

    /**
     * Handle a scan (RFID/Face/Fingerprint).
     */
    public function scan(Request $request)
    {
        $data = $request->validate([
            'identifier' => 'required|string',
            'method'     => 'required|in:rfid,face,fingerprint',
        ]);

        try {
            $scanResult = $this->attendanceService->processScan(
                $data['identifier'],
                $data['method']
            );
        } catch (ValidationException $e) {
            throw $e; // Let Laravel/Inertia handle validation errors
        }

        $summary = $this->attendanceService->todaySummary();

        return Inertia::render('Attendance/Scan', array_merge([
            'success' => true,
        ], $scanResult, $summary, [
            'signInEndTime' => $this->attendanceService->getSignInEndTime(),
            'timeRestrictionEnabled' => $this->attendanceService->isTimeRestrictionEnabled(), // bool
            'serverNow' => now()->toDateTimeString(), // helpful to sync client/server time
        ]));
    }

    /**
     * Simple log listing (optional dashboard).
     */
    public function today()
    {
        return Inertia::render('Attendance/Today', [
            'logs' => \App\Models\AttendanceLog::with('user')
                ->whereDate('logged_at', now())
                ->latest()
                ->get(),
        ]);
    }
}
