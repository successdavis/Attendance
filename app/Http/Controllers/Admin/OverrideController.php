<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AttendanceSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OverrideController extends Controller
{
    public function index(): Response
    {
        $overrides = \DB::table('attendance_overrides')
            ->orderByDesc('date')
            ->get();

        return Inertia::render('Admin/Settings/Overrides/Index', compact('overrides'));
    }

    public function store(Request $request, AttendanceSettingsService $settings): RedirectResponse
    {
        $data = $request->validate([
            'date'              => 'required|date|unique:attendance_overrides,date',
            'sign_in_start'     => 'nullable|date_format:H:i',
            'sign_in_end'       => 'nullable|date_format:H:i',
            'sign_out_start'    => 'nullable|date_format:H:i',
            'sign_out_end'      => 'nullable|date_format:H:i',
            'limit_enabled'     => 'boolean',
            'allow_multiple_daily' => 'boolean',
            'note'              => 'nullable|string|max:500',
        ]);

        \DB::table('attendance_overrides')->insert(array_merge($data, [
            'created_by' => $request->user()->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        $settings->flush();

        return back()->with('success', 'Override added.');
    }

    public function update(Request $request, string $date, AttendanceSettingsService $settings): RedirectResponse
    {
        $data = $request->validate([
            'sign_in_start'     => 'nullable|date_format:H:i',
            'sign_in_end'       => 'nullable|date_format:H:i',
            'sign_out_start'    => 'nullable|date_format:H:i',
            'sign_out_end'      => 'nullable|date_format:H:i',
            'limit_enabled'     => 'boolean',
            'allow_multiple_daily' => 'boolean',
            'note'              => 'nullable|string|max:500',
        ]);

        \DB::table('attendance_overrides')->where('date', $date)->update(array_merge($data, ['updated_at' => now()]));

        $settings->flush();

        return back()->with('success', 'Override updated.');
    }

    public function destroy(string $date, AttendanceSettingsService $settings): RedirectResponse
    {
        \DB::table('attendance_overrides')->where('date', $date)->delete();

        $settings->flush();

        return back()->with('success', 'Override removed.');
    }
}
