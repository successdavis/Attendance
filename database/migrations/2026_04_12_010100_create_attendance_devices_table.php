<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendance_devices', function (Blueprint $table) {
            $table->id();

            // Admin-assigned unique identifier (e.g. hardware serial number or alias)
            $table->string('device_uid', 100)->unique();

            $table->string('name', 150);

            $table->enum('type', ['rfid_reader', 'fingerprint_scanner', 'face_camera', 'multi']);

            // Physical location and branch assignment
            $table->string('location', 200)->nullable();
            $table->string('branch', 100)->nullable();

            // Network details for IP-based trust checking
            $table->string('ip_address', 45)->nullable();

            // SHA-256 hash of the device API token (plain token shown once, never stored)
            $table->string('api_token_hash', 255)->unique();

            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active');

            // Heartbeat tracking
            $table->timestamp('last_seen_at')->nullable();
            $table->string('last_ip', 45)->nullable();
            $table->string('firmware_version', 50)->nullable();

            // Optional per-device config overrides (JSON blob)
            $table->json('config_json')->nullable();

            $table->foreignId('registered_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->timestamps();

            $table->index('status');
            $table->index('branch');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_devices');
    }
};
