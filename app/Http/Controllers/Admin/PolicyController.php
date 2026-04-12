<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendancePolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PolicyController extends Controller
{
    public function index(): Response
    {
        $policies = AttendancePolicy::withCount('users')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Policies/Index', compact('policies'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'                      => 'required|string|max:150|unique:attendance_policies,name',
            'description'               => 'nullable|string|max:500',
            'sign_in_start'             => 'nullable|date_format:H:i',
            'sign_in_end'               => 'nullable|date_format:H:i',
            'sign_out_start'            => 'nullable|date_format:H:i',
            'sign_out_end'              => 'nullable|date_format:H:i',
            'grace_period_minutes'      => 'nullable|integer|min:0|max:120',
            'late_threshold_minutes'    => 'nullable|integer|min:0|max:480',
            'allow_multiple_daily'      => 'boolean',
            'weekend_allowed'           => 'boolean',
            'time_restriction_enabled'  => 'boolean',
            'is_default'                => 'boolean',
        ]);

        if (! empty($data['is_default'])) {
            AttendancePolicy::where('is_default', true)->update(['is_default' => false]);
        }

        AttendancePolicy::create(array_merge($data, ['created_by' => $request->user()->id]));

        return back()->with('success', 'Policy created.');
    }

    public function update(Request $request, AttendancePolicy $policy): RedirectResponse
    {
        $data = $request->validate([
            'name'                      => "sometimes|string|max:150|unique:attendance_policies,name,{$policy->id}",
            'description'               => 'sometimes|nullable|string|max:500',
            'sign_in_start'             => 'sometimes|nullable|date_format:H:i',
            'sign_in_end'               => 'sometimes|nullable|date_format:H:i',
            'sign_out_start'            => 'sometimes|nullable|date_format:H:i',
            'sign_out_end'              => 'sometimes|nullable|date_format:H:i',
            'grace_period_minutes'      => 'sometimes|nullable|integer|min:0|max:120',
            'late_threshold_minutes'    => 'sometimes|nullable|integer|min:0|max:480',
            'allow_multiple_daily'      => 'sometimes|boolean',
            'weekend_allowed'           => 'sometimes|boolean',
            'time_restriction_enabled'  => 'sometimes|boolean',
            'is_active'                 => 'sometimes|boolean',
            'is_default'                => 'sometimes|boolean',
        ]);

        if (! empty($data['is_default'])) {
            AttendancePolicy::where('is_default', true)->where('id', '!=', $policy->id)->update(['is_default' => false]);
        }

        $policy->update($data);

        return back()->with('success', 'Policy updated.');
    }

    public function destroy(AttendancePolicy $policy): RedirectResponse
    {
        // Unassign users from this policy before deleting
        $policy->users()->update(['policy_id' => null]);
        $policy->delete();

        return back()->with('success', 'Policy deleted.');
    }

    public function assign(Request $request, AttendancePolicy $policy): RedirectResponse
    {
        $data = $request->validate([
            'user_ids'   => 'required|array|min:1',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        \DB::table('users')->whereIn('id', $data['user_ids'])->update(['policy_id' => $policy->id]);

        return back()->with('success', count($data['user_ids']) . ' user(s) assigned to policy.');
    }
}
