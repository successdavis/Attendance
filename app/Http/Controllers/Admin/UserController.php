<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\AttendancePolicy;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Laravel\Facades\Image;
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
            $data['profile_photo_path'] = $this->storeOptimisedPhoto($request->file('profile_photo'));
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
        \Log::info('update() called', [
            'user_id'       => $user->id,
            'has_file'      => $request->hasFile('profile_photo'),
            'all_files'     => array_keys($request->allFiles()),
            'method'        => $request->method(),
            'content_type'  => $request->header('Content-Type'),
        ]);

        $data = $request->validated();

        // Remove existing photo if requested
        if (! empty($data['remove_photo']) && $user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
            $data['profile_photo_path'] = null;
        }

        // Replace photo if a new one was uploaded
        if ($request->hasFile('profile_photo')) {
            \Log::info('update() processing uploaded photo');
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }
            try {
                $data['profile_photo_path'] = $this->storeOptimisedPhoto($request->file('profile_photo'));
                \Log::info('update() photo saved', ['path' => $data['profile_photo_path']]);
            } catch (\Throwable $e) {
                \Log::error('update() photo save failed', ['error' => $e->getMessage()]);
            }
        }

        unset($data['remove_photo']);

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

    /**
     * POST /admin/users/{user}/photo
     * Immediately replace a user's profile photo (called by the Edit page
     * as soon as the admin captures or uploads a photo — no full form submit needed).
     */
    public function updatePhoto(Request $request, User $user): \Illuminate\Http\JsonResponse
    {
        \Log::info('updatePhoto called', [
            'user_id'    => $user->id,
            'has_file'   => $request->hasFile('profile_photo'),
            'all_files'  => array_keys($request->allFiles()),
            'method'     => $request->method(),
            'content_type' => $request->header('Content-Type'),
        ]);

        $request->validate([
            'profile_photo' => 'required|image|mimes:jpg,jpeg,png,webp,gif|max:10240',
        ]);

        \Log::info('updatePhoto validation passed');

        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        try {
            $path = $this->storeOptimisedPhoto($request->file('profile_photo'));
            \Log::info('updatePhoto stored', ['path' => $path]);
        } catch (\Throwable $e) {
            \Log::error('updatePhoto storeOptimisedPhoto failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }

        $user->update(['profile_photo_path' => $path]);
        $user->refresh();

        \Log::info('updatePhoto DB updated', ['url' => $user->profile_photo_url]);

        activity()
            ->causedBy($request->user())
            ->performedOn($user)
            ->log('Updated profile photo');

        return response()->json([
            'url' => $user->profile_photo_url,
        ]);
    }

    /**
     * DELETE /admin/users/{user}/photo
     * Remove a user's profile photo.
     */
    public function destroyPhoto(Request $request, User $user): \Illuminate\Http\JsonResponse
    {
        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
            $user->update(['profile_photo_path' => null]);

            activity()
                ->causedBy($request->user())
                ->performedOn($user)
                ->log('Removed profile photo');
        }

        return response()->json(['url' => '']);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────────

    /**
     * Resize the uploaded photo to max 400×400 px, convert to JPEG at 88% quality,
     * and store it in storage/app/public/profile-photos/.
     *
     * The frontend already does client-side optimisation (Canvas API), so this
     * acts as a server-side safety net for any uploads that bypass the UI.
     *
     * @return string  The relative storage path (suitable for Storage::url())
     */
    private function storeOptimisedPhoto(UploadedFile $file): string
    {
        $filename = 'profile-photos/' . \Str::uuid() . '.jpg';

        $image = Image::decode($file->getRealPath())
            ->scaleDown(width: 400, height: 400)   // preserves aspect ratio, never upscales
            ->encode(new JpegEncoder(quality: 88));

        Storage::disk('public')->put($filename, $image);

        return $filename;
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
