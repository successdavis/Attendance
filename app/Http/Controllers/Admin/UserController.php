<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\AttendancePolicy;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Facades\Activity;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $users = User::query()
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%"))
            ->when($request->role,   fn ($q, $r) => $q->where('role', $r))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->branch, fn ($q, $b) => $q->where('branch', $b))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users'   => $users,
            'filters' => $request->only('search', 'role', 'status', 'branch'),
        ]);
    }

    public function show(User $user): Response
    {
        $user->load('policy');

        $credentials = $user->credentials()->with('device')->get();

        $recentLogs = $user->attendanceLogs()
            ->with('device')
            ->latest('logged_at')
            ->limit(20)
            ->get();

        return Inertia::render('Admin/Users/Show', compact('user', 'recentLogs', 'credentials'));
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Users/Create', [
            'policies' => AttendancePolicy::where('is_active', true)->get(['id', 'name']),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('profile_photo')) {
            $data['profile_photo_path'] = $request->file('profile_photo')
                ->store('profile-photos', 'public');
        }

        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);
        $user->assignRole($data['role']);

        activity()
            ->causedBy($request->user())
            ->performedOn($user)
            ->log('Created user account');

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user): Response
    {
        $user->load('policy');

        return Inertia::render('Admin/Users/Edit', [
            'user'     => $user,
            'policies' => AttendancePolicy::where('is_active', true)->get(['id', 'name']),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('profile_photo')) {
            // Delete old photo if exists
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }
            $data['profile_photo_path'] = $request->file('profile_photo')
                ->store('profile-photos', 'public');
        }

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        if (isset($data['role'])) {
            $user->syncRoles([$data['role']]);
        }

        return back()->with('success', 'User updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        // Prevent deleting your own account
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted.');
    }

    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'status' => 'required|in:active,inactive,suspended',
            'reason' => 'nullable|string|max:500',
        ]);

        $oldStatus = $user->status;
        $user->update(['status' => $request->status]);

        // Record status change in user_status_logs
        \DB::table('user_status_logs')->insert([
            'user_id'    => $user->id,
            'old_status' => $oldStatus,
            'new_status' => $request->status,
            'changed_by' => $request->user()->id,
            'changed_at' => now(),
        ]);

        activity()
            ->causedBy($request->user())
            ->performedOn($user)
            ->withProperties(['old' => $oldStatus, 'new' => $request->status, 'reason' => $request->reason])
            ->log('Updated user status');

        return back()->with('success', "User status updated to {$request->status}.");
    }
}
