<?php

namespace App\Http\Requests\Admin;

use App\Models\AttendanceSetting;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        // Dynamically build validation rules from the settings table
        // so unknown keys are rejected without needing to hardcode every key.
        $keys = AttendanceSetting::pluck('key')->all();

        $rules = [];

        foreach ($keys as $key) {
            $rules[$key] = 'sometimes|nullable';
        }

        // More specific rules for known critical settings
        $rules['scan_debounce_seconds']  = 'sometimes|integer|min:5|max:3600';
        $rules['grace_period_minutes']   = 'sometimes|integer|min:0|max:120';
        $rules['late_threshold_minutes'] = 'sometimes|integer|min:0|max:480';
        $rules['sign_in_start_time']     = 'sometimes|nullable|date_format:H:i';
        $rules['sign_in_end_time']       = 'sometimes|nullable|date_format:H:i';
        $rules['sign_out_start_time']    = 'sometimes|nullable|date_format:H:i';
        $rules['sign_out_end_time']      = 'sometimes|nullable|date_format:H:i';
        $rules['auto_sign_out_time']     = 'sometimes|nullable|date_format:H:i';
        $rules['sync_api_url']           = 'sometimes|nullable|url';
        $rules['time_format']            = 'sometimes|in:12h,24h';
        $rules['allowed_methods']        = 'sometimes|array';
        $rules['allowed_methods.*']      = 'in:rfid,fingerprint,face';

        return $rules;
    }
}
