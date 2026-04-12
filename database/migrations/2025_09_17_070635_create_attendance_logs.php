<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();

            // Link to the user
            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete();

            // Which method was used: rfid / face / fingerprint
            $table->enum('method', ['rfid', 'face', 'fingerprint']);

            // Stores the raw identifier used during the scan
            $table->string('identifier');

            // Check-in or Check-out
            $table->enum('status', ['check_in', 'check_out'])->default('check_in');

            $table->timestamp('logged_at')->useCurrent();

            // Indexes for fast lookups by user+date and date-only queries
            $table->index(['user_id', 'logged_at']);
            $table->index('logged_at');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
    }
};
