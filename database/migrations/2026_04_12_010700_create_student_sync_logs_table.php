<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_sync_logs', function (Blueprint $table) {
            $table->id();

            // What initiated this sync run
            $table->enum('triggered_by', ['schedule', 'manual', 'command'])->default('schedule');

            // Admin who clicked "Sync Now" (null for scheduled/command runs)
            $table->foreignId('triggered_by_user')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();

            $table->enum('status', ['running', 'completed', 'failed', 'partial'])->default('running');

            // Counters for the sync summary panel
            $table->unsignedInteger('total_fetched')->default(0);
            $table->unsignedInteger('total_created')->default(0);
            $table->unsignedInteger('total_updated')->default(0);
            $table->unsignedInteger('total_deactivated')->default(0);
            $table->unsignedInteger('total_skipped')->default(0);

            // Failure details
            $table->text('error_message')->nullable();

            // Snapshot of last API response metadata (page counts, etc.) for debugging
            $table->json('response_snapshot')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_sync_logs');
    }
};
