<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Clear the permission cache before seeding
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define all granular permissions
        $permissions = [
            // User management
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'users.manage-status',

            // Credential (RFID / biometric) management
            'credentials.view',
            'credentials.create',
            'credentials.delete',

            // Device management
            'devices.view',
            'devices.create',
            'devices.edit',
            'devices.delete',
            'devices.rotate-token',

            // Attendance settings
            'settings.view',
            'settings.edit',

            // Attendance policies
            'policies.view',
            'policies.create',
            'policies.edit',
            'policies.delete',

            // Reporting
            'reports.view',
            'reports.export',

            // Manual corrections to attendance records
            'corrections.create',
            'corrections.approve',

            // Student sync integration
            'sync.view',
            'sync.trigger',

            // Audit log
            'audit.view',

            // Daily schedule overrides
            'overrides.manage',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // ─── Admin role — full access ────────────────────────────────────────────
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions($permissions);

        // ─── Staff role — limited operational access ─────────────────────────────
        $staff = Role::firstOrCreate(['name' => 'staff']);
        $staff->syncPermissions([
            'users.view',
            'reports.view',
            'reports.export',
            'corrections.create',
        ]);

        // ─── Student role — no admin access ──────────────────────────────────────
        Role::firstOrCreate(['name' => 'student']);
        // Students have no admin permissions — they only interact via device scans
    }
}
