<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceService $attendanceService
    ) {}

    /**
     * Show the attendance scan page.
     * Passes allowed methods and relevant settings to the frontend.
     */
    public function showScan(): Response
    {
        return Inertia::render('Attendance/Scan', [
            'settings' => $this->attendanceService->frontendSettings(),
        ]);
    }

    /**
     * Handle an attendance scan submitted from the web UI (RFID keyboard emulator).
     */
    public function scan(Request $request): JsonResponse
    {
        $data = $request->validate([
            'identifier' => 'required|string',
            'method'     => 'required|in:rfid,face,fingerprint',
        ]);

        // No device context for web-based scans (device = null)
        $result = $this->attendanceService->processScan(
            identifier: $data['identifier'],
            method:     $data['method'],
            device:     null,
        );

        $user = $result['user'];
        $log  = $result['log'];

        return response()->json([
            'success' => true,
            'status'  => $log->status,
            'message' => $log->status === 'sign_in' ? 'Signed in successfully.' : 'Signed out successfully.',
            'user'    => ['name' => $user->name, 'id' => $user->id],
        ]);
    }

    /**
     * Simple log listing (optional dashboard).
     */
    public function today(): Response
    {
        $logs = AttendanceLog::with('user')
            ->whereDate('logged_at', now())
            ->latest('logged_at')
            ->get();

        return Inertia::render('Attendance/Today', [
            'logs' => $logs,
        ]);
    }
}
