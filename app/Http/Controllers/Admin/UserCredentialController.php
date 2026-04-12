<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserCredential;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserCredentialController extends Controller
{
    public function index(User $user): Response
    {
        $credentials = $user->credentials()->with('device', 'enrolledBy')->get();

        return Inertia::render('Admin/Users/Credentials', compact('user', 'credentials'));
    }

    public function store(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'type'  => 'required|in:rfid,fingerprint,face',
            'value' => 'required|string|max:500',
            'label' => 'nullable|string|max:100',
        ]);

        // Check for duplicate credential value across all users
        if (UserCredential::where('type', $data['type'])->where('value', $data['value'])->exists()) {
            return back()->withErrors(['value' => 'This credential is already assigned to another user.']);
        }

        $user->credentials()->create(array_merge($data, [
            'enrolled_at' => now(),
            'enrolled_by' => $request->user()->id,
            'is_active'   => true,
        ]));

        activity()
            ->causedBy($request->user())
            ->performedOn($user)
            ->withProperties(['type' => $data['type'], 'label' => $data['label']])
            ->log('Assigned credential to user');

        return back()->with('success', 'Credential assigned successfully.');
    }

    public function destroy(Request $request, User $user, UserCredential $credential): RedirectResponse
    {
        // Soft-revoke instead of hard delete to preserve audit trail
        $credential->update([
            'is_active'  => false,
            'revoked_at' => now(),
            'revoked_by' => $request->user()->id,
        ]);

        activity()
            ->causedBy($request->user())
            ->performedOn($user)
            ->withProperties(['type' => $credential->type, 'credential_id' => $credential->id])
            ->log('Revoked credential from user');

        return back()->with('success', 'Credential revoked.');
    }
}
