<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttendanceSettingsSeeder extends Seeder
{
    /**
     * All default attendance settings.
     * Values match the previous hardcoded defaults in AttendanceController.
     * Admins can override any of these via the settings admin page.
     */
    public function run(): void
    {
        $settings = [
            // ─── General ────────────────────────────────────────────────────────────
            [
                'key'         => 'scan_debounce_seconds',
                'value'       => '60',
                'type'        => 'integer',
                'group'       => 'general',
                'label'       => 'Scan Debounce (seconds)',
                'description' => 'Minimum seconds that must elapse between two scans for the same user. Prevents accidental double-tap.',
            ],
            [
                'key'         => 'allow_multiple_daily',
                'value'       => '0',
                'type'        => 'boolean',
                'group'       => 'general',
                'label'       => 'Allow Multiple Check-in/Out Per Day',
                'description' => 'When enabled, users can check in and out multiple times in a single day.',
            ],
            [
                'key'         => 'auto_sign_out_enabled',
                'value'       => '0',
                'type'        => 'boolean',
                'group'       => 'general',
                'label'       => 'Enable Auto Sign-out',
                'description' => 'Automatically create a check-out record for any user still checked in at the end of the day.',
            ],
            [
                'key'         => 'auto_sign_out_time',
                'value'       => '18:00',
                'type'        => 'time',
                'group'       => 'general',
                'label'       => 'Auto Sign-out Time',
                'description' => 'Time at which automatic sign-out is triggered (used when auto_sign_out_enabled is on).',
            ],

            // ─── Schedule ────────────────────────────────────────────────────────────
            [
                'key'         => 'time_restriction_enabled',
                'value'       => '0',
                'type'        => 'boolean',
                'group'       => 'schedule',
                'label'       => 'Enforce Time Windows',
                'description' => 'When enabled, scans outside the configured sign-in and sign-out windows are rejected.',
            ],
            [
                'key'         => 'sign_in_start_time',
                'value'       => '07:00',
                'type'        => 'time',
                'group'       => 'schedule',
                'label'       => 'Sign-in Window Opens At',
                'description' => 'Earliest time a valid check-in scan is accepted.',
            ],
            [
                'key'         => 'sign_in_end_time',
                'value'       => '10:00',
                'type'        => 'time',
                'group'       => 'schedule',
                'label'       => 'Sign-in Window Closes At',
                'description' => 'Latest time a valid check-in scan is accepted (grace period added on top of this).',
            ],
            [
                'key'         => 'sign_out_start_time',
                'value'       => '16:00',
                'type'        => 'time',
                'group'       => 'schedule',
                'label'       => 'Sign-out Window Opens At',
                'description' => 'Earliest time a valid check-out scan is accepted.',
            ],
            [
                'key'         => 'sign_out_end_time',
                'value'       => '20:00',
                'type'        => 'time',
                'group'       => 'schedule',
                'label'       => 'Sign-out Window Closes At',
                'description' => 'Latest time a valid check-out scan is accepted.',
            ],
            [
                'key'         => 'grace_period_minutes',
                'value'       => '15',
                'type'        => 'integer',
                'group'       => 'schedule',
                'label'       => 'Grace Period (minutes)',
                'description' => 'Extra minutes allowed beyond sign_in_end_time before the user is marked as late.',
            ],
            [
                'key'         => 'late_threshold_minutes',
                'value'       => '30',
                'type'        => 'integer',
                'group'       => 'schedule',
                'label'       => 'Late Threshold (minutes)',
                'description' => 'Minutes after sign_in_start_time after which a user is classified as late.',
            ],
            [
                'key'         => 'weekend_attendance_allowed',
                'value'       => '0',
                'type'        => 'boolean',
                'group'       => 'schedule',
                'label'       => 'Allow Weekend Attendance',
                'description' => 'When disabled, scans on Saturday and Sunday are rejected.',
            ],

            // ─── Security ────────────────────────────────────────────────────────────
            [
                'key'         => 'allowed_methods',
                'value'       => '["rfid"]',
                'type'        => 'json',
                'group'       => 'security',
                'label'       => 'Enabled Attendance Methods',
                'description' => 'List of authentication methods accepted for attendance. Possible values: rfid, fingerprint, face.',
            ],
            [
                'key'         => 'anti_passback_enabled',
                'value'       => '0',
                'type'        => 'boolean',
                'group'       => 'security',
                'label'       => 'Anti-passback',
                'description' => 'Prevent a user from checking in again without first checking out, and vice versa.',
            ],

            // ─── Student Sync ────────────────────────────────────────────────────────
            [
                'key'         => 'program_expiry_enforcement',
                'value'       => '1',
                'type'        => 'boolean',
                'group'       => 'sync',
                'label'       => 'Enforce Program Expiry',
                'description' => 'When enabled, students whose program_expires_at date has passed cannot scan in.',
            ],
            [
                'key'         => 'sync_api_url',
                'value'       => null,
                'type'        => 'string',
                'group'       => 'sync',
                'label'       => 'External Student API Base URL',
                'description' => 'The base URL of the external training website student API, e.g. https://training.example.com/api',
            ],
            [
                'key'         => 'sync_api_key',
                'value'       => null,
                'type'        => 'string',
                'group'       => 'sync',
                'label'       => 'External Student API Key',
                'description' => 'Bearer token used to authenticate requests to the external student API.',
            ],
            [
                'key'         => 'sync_schedule_enabled',
                'value'       => '0',
                'type'        => 'boolean',
                'group'       => 'sync',
                'label'       => 'Enable Scheduled Sync',
                'description' => 'Automatically sync students from the external API on the configured schedule.',
            ],
            [
                'key'         => 'sync_schedule_cron',
                'value'       => '0 6 * * *',
                'type'        => 'string',
                'group'       => 'sync',
                'label'       => 'Sync Cron Expression',
                'description' => 'Cron expression for automatic sync (default: every day at 06:00).',
            ],
            [
                'key'         => 'sync_deactivate_missing',
                'value'       => '1',
                'type'        => 'boolean',
                'group'       => 'sync',
                'label'       => 'Deactivate Missing Students',
                'description' => 'Set students to inactive if they are no longer returned by the external API.',
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('attendance_settings')->updateOrInsert(
                ['key' => $setting['key']],
                array_merge($setting, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
