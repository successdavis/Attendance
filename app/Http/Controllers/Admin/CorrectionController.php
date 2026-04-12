<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceCorrection;
use App\Models\AttendanceLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class CorrectionController extends Controller
{
    public function index(): Response
    {
        $corrections = AttendanceCorrection::with('user', 'correctedBy', 'attendanceLog')
            ->orderByDesc('corrected_at')
            ->paginate(50);

        return Inertia::render('Admin/Corrections/Index', compact('corrections'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id'   => 'required|exists:users,id',
            'logged_at' => 'required|date',
            'status'    => 'required|in:sign_in,sign_out',
            'reason'    => 'required|string|max:500',
            'location'  => 'nullable|string|max:200',
            'note'      => 'nullable|string|max:500',
        ]);

        $log = AttendanceLog::create([
            'user_id'    => $data['user_id'],
            'status'     => $data['status'],
            'logged_at'  => Carbon::parse($data['logged_at']),
            'location'   => $data['location'] ?? null,
            'is_manual'  => true,
            'created_by' => $request->user()->id,
        ]);

        AttendanceCorrection::create([
            'attendance_log_id' => $log->id,
            'user_id'           => $data['user_id'],
            'action'            => 'create',
            'original_data'     => null,
            'corrected_data'    => $log->toArray(),
            'reason'            => $data['reason'],
            'corrected_by'      => $request->user()->id,
            'corrected_at'      => now(),
        ]);

        activity()
            ->causedBy($request->user())
            ->performedOn($log)
            ->withProperties(['reason' => $data['reason']])
            ->log('Manual attendance log created');

        return back()->with('success', 'Manual attendance record created.');
    }

    public function update(Request $request, AttendanceLog $log): RedirectResponse
    {
        $data = $request->validate([
            'logged_at' => 'sometimes|date',
            'status'    => 'sometimes|in:sign_in,sign_out',
            'location'  => 'sometimes|nullable|string|max:200',
            'reason'    => 'required|string|max:500',
        ]);

        $original = $log->toArray();

        $log->update([
            'logged_at' => isset($data['logged_at']) ? Carbon::parse($data['logged_at']) : $log->logged_at,
            'status'    => $data['status'] ?? $log->status,
            'location'  => array_key_exists('location', $data) ? $data['location'] : $log->location,
        ]);

        AttendanceCorrection::create([
            'attendance_log_id' => $log->id,
            'user_id'           => $log->user_id,
            'action'            => 'edit',
            'original_data'     => $original,
            'corrected_data'    => $log->fresh()->toArray(),
            'reason'            => $data['reason'],
            'corrected_by'      => $request->user()->id,
            'corrected_at'      => now(),
        ]);

        activity()
            ->causedBy($request->user())
            ->performedOn($log)
            ->withProperties(['reason' => $data['reason']])
            ->log('Attendance log corrected');

        return back()->with('success', 'Attendance record corrected.');
    }

    public function destroy(Request $request, AttendanceLog $log): RedirectResponse
    {
        $data = $request->validate(['reason' => 'required|string|max:500']);

        AttendanceCorrection::create([
            'attendance_log_id' => $log->id,
            'user_id'           => $log->user_id,
            'action'            => 'delete',
            'original_data'     => $log->toArray(),
            'corrected_data'    => [],
            'reason'            => $data['reason'],
            'corrected_by'      => $request->user()->id,
            'corrected_at'      => now(),
        ]);

        $log->delete();

        return back()->with('success', 'Attendance record deleted.');
    }
}
