<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            // Which physical device recorded this log (null for web-based scans)
            $table->foreignId('device_id')
                  ->nullable()
                  ->after('method')
                  ->constrained('attendance_devices')
                  ->nullOnDelete();

            // Captured location at time of scan (from device config or IP geolocation)
            $table->string('location', 200)->nullable()->after('device_id');

            // For manual corrections — why this log was created/modified manually
            $table->string('override_reason', 500)->nullable()->after('location');

            // True when created by an admin correction rather than an actual scan
            $table->boolean('is_manual')->default(false)->after('override_reason');

            // Admin who created this log (null for actual device scans)
            $table->foreignId('created_by')
                  ->nullable()
                  ->after('is_manual')
                  ->constrained('users')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->dropForeign(['device_id']);
            $table->dropForeign(['created_by']);
            $table->dropColumn(['device_id', 'location', 'override_reason', 'is_manual', 'created_by']);
        });
    }
};
