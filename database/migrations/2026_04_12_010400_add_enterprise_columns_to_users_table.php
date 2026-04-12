<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // External student website integration
            // Stores the immutable student ID from the training website (used as upsert key)
            $table->string('external_student_id', 100)->unique()->nullable()->after('profile_photo_path');
            $table->timestamp('external_synced_at')->nullable()->after('external_student_id');

            // Program/enrollment expiry for students
            $table->timestamp('program_expires_at')->nullable()->after('external_synced_at');

            // Branch or campus assignment
            $table->string('branch', 100)->nullable()->after('program_expires_at');

            // Admin notes about this user
            $table->text('notes')->nullable()->after('branch');

            // Assigned attendance policy (null = use the default policy or global settings)
            $table->foreignId('policy_id')
                  ->nullable()
                  ->after('notes')
                  ->constrained('attendance_policies')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['policy_id']);
            $table->dropColumn([
                'external_student_id',
                'external_synced_at',
                'program_expires_at',
                'branch',
                'notes',
                'policy_id',
            ]);
        });
    }
};
