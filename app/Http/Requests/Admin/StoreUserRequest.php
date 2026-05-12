<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('users.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email',
            'password'      => ['required', Password::min(8)->mixedCase()->numbers()],
            'role'          => 'required|in:admin,staff,student',
            'status'        => 'required|in:active,inactive,suspended',
            'branch'        => 'nullable|string|max:100',
            'notes'         => 'nullable|string|max:1000',
            'policy_id'     => 'nullable|exists:attendance_policies,id',
            'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:10240',

            // Student-specific fields
            'external_student_id' => 'nullable|string|max:100|unique:users,external_student_id',
            'program_expires_at'  => 'nullable|date',
        ];
    }
}
