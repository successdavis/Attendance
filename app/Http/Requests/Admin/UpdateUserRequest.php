<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('users.edit') ?? false;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name'          => 'sometimes|string|max:255',
            'email'         => "sometimes|email|unique:users,email,{$userId}",
            'password'      => ['sometimes', 'nullable', Password::min(8)->mixedCase()->numbers()],
            'role'          => 'sometimes|in:admin,staff,student',
            'status'        => 'sometimes|in:active,inactive,suspended',
            'branch'        => 'sometimes|nullable|string|max:100',
            'notes'         => 'sometimes|nullable|string|max:1000',
            'policy_id'     => 'sometimes|nullable|exists:attendance_policies,id',
            'profile_photo' => 'sometimes|nullable|image|max:2048',
            'external_student_id' => "sometimes|nullable|string|max:100|unique:users,external_student_id,{$userId}",
            'program_expires_at'  => 'sometimes|nullable|date',
        ];
    }
}
