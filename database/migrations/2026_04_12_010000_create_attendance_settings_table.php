<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendance_settings', function (Blueprint $table) {
            $table->id();

            // Unique dot-notated key, e.g. "schedule.sign_in_start_time"
            $table->string('key', 150)->unique();

            // The stored value (always a string; typed reading is handled in service)
            $table->text('value')->nullable();

            // Informs the settings service how to cast the value
            $table->enum('type', ['string', 'integer', 'boolean', 'time', 'json', 'array'])
                  ->default('string');

            // Logical grouping for admin UI display (general, schedule, sync, security, reports)
            $table->string('group', 80)->default('general');

            // Human-readable label and description for the admin settings page
            $table->string('label', 200);
            $table->text('description')->nullable();

            // Who last updated this setting (for audit trail)
            $table->foreignId('updated_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->timestamps();

            $table->index('group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_settings');
    }
};
