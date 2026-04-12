<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendance_corrections', function (Blueprint $table) {
            $table->id();

            // The log being corrected (null when action = 'create' — no pre-existing log)
            $table->foreignId('attendance_log_id')
                  ->nullable()
                  ->constrained('attendance_logs')
                  ->nullOnDelete();

            // Whose attendance record is being corrected
            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->enum('action', ['create', 'edit', 'delete']);

            // JSON snapshot of the attendance_log row before correction (null for 'create')
            $table->json('original_data')->nullable();

            // JSON of the values that were set / created
            $table->json('corrected_data');

            // Mandatory reason — supports compliance and auditing
            $table->string('reason', 500);

            $table->foreignId('corrected_by')
                  ->constrained('users');

            $table->timestamp('corrected_at')->useCurrent();

            $table->index('user_id');
            $table->index('corrected_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_corrections');
    }
};
