<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AttendanceLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceController extends Controller
{
    /**
     * Handle an attendance scan (RFID/Face/Fingerprint).
     */
    public function scan(Request $request)
    {
        $data = $request->validate([
            'identifier' => 'required|string',
            'method'     => 'required|in:rfid,face,fingerprint',
        ]);

        $user = match ($data['method']) {
            'rfid'        => User::where('rfid_uid', $data['identifier'])->first(),
            'face'        => User::where('face_template_id', $data['identifier'])->first(),
            'fingerprint' => User::where('fingerprint_template_id', $data['identifier'])->first(),
        };

        if (! $user) {
            throw ValidationException::withMessages([
                'identifier' => 'User not found for this credential.',
            ]);
        }

        if (! $user->isActive()) {
            throw ValidationException::withMessages([
                'identifier' => 'This user is inactive or suspended.',
            ]);
        }

        // Prevent multiple logs within 1 minute
        $latestLog = $user->attendanceLogs()->latest()->first();0013621535


        if ($latestLog && $latestLog->logged_at->diffInSeconds(now()) < 60) {
            return response()->json([
                'success' => false,
                'message' => 'Duplicate scan detected. Please wait a moment.',
                'user'    => [
                    'name'      => $user->name,
                    'photo_url' => $user->profilePhotoUrl(),
                ],
                'log' => [
                    'status'    => $latestLog->status,
                    'logged_at' => $latestLog->logged_at->toDateTimeString(),
                ],
            ], 429); // HTTP 429 Too Many Requests
        }

        $nextStatus = $latestLog && $latestLog->status === 'check_in'
            ? 'check_out'
            : 'check_in';

        $log = DB::transaction(fn () =>
        AttendanceLog::create([
            'user_id'    => $user->id,
            'method'     => $data['method'],
            'identifier' => $data['identifier'],
            'status'     => $nextStatus,
            'logged_at'  => now(),
        ])
        );

        return response()->json([
            'success' => true,
            'user'    => [
                'id'        => $user->id,
                'name'      => $user->name,
                'role'      => $user->role,
                'photo_url' => $user->profilePhotoUrl(),
            ],
            'log' => [
                'status'    => $log->status,
                'logged_at' => $log->logged_at->toDateTimeString(),
            ],
        ]);
    }


    /**
     * Show today's attendance dashboard.
     */
    public function today()
    {
        $logs = AttendanceLog::with('user')
            ->whereDate('logged_at', now())
            ->latest()
            ->get();

        return inertia('Attendance/Today', [
            'logs' => $logs,
        ]);
    }
}
