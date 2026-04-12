<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seed roles and permissions first (Spatie)
        $this->call(RolesAndPermissionsSeeder::class);

        // Seed all attendance settings with defaults
        $this->call(AttendanceSettingsSeeder::class);

        // Create a default admin account if one doesn't exist
        $admin = User::firstOrCreate(
            ['email' => 'admin@attendance.local'],
            [
                'name'     => 'System Admin',
                'password' => Hash::make('Admin@123!'),
                'role'     => 'admin',
                'status'   => 'active',
            ]
        );

        // Assign the admin Spatie role so permissions work via middleware
        $admin->assignRole('admin');
    }
}
