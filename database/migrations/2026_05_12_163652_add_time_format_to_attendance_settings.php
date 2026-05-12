<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('attendance_settings')->where('key', 'time_format')->doesntExist()) {
            DB::table('attendance_settings')->insert([
                'key'         => 'time_format',
                'value'       => '12h',
                'type'        => 'string',
                'group'       => 'general',
                'label'       => 'Time Format',
                'description' => 'Display times in 12-hour (AM/PM) or 24-hour format across the attendance scanner and reports.',
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('attendance_settings')->where('key', 'time_format')->delete();
    }
};
