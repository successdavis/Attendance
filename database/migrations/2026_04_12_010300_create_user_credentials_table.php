<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Replace the flat rfid_uid / face_template_id / fingerprint_template_id columns on users
     * with a flexible credential table that supports one user having multiple credentials
     * across multiple methods and devices.
     */
    public function up(): void
    {
        Schema::create('user_credentials', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete();

            // The authentication method this credential is for
            $table->enum('type', ['rfid', 'fingerprint', 'face']);

            // The raw credential value:
            //   rfid        → card UID string
            //   fingerprint → template hash/reference returned by scanner SDK
            //   face        → template hash/reference returned by face SDK
            $table->string('value', 500);

            // Which device enrolled this credential (optional — manual admin entry has no device)
            $table->foreignId('device_id')
                  ->nullable()
                  ->constrained('attendance_devices')
                  ->nullOnDelete();

            // Optional human label, e.g. "Right index finger", "Primary RFID card"
            $table->string('label', 100)->nullable();

            $table->timestamp('enrolled_at')->nullable();

            $table->foreignId('enrolled_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            // Soft revocation — revoked credentials are kept for audit purposes
            $table->boolean('is_active')->default(true);
            $table->timestamp('revoked_at')->nullable();

            $table->foreignId('revoked_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->timestamps();

            // A credential value must be globally unique per type to prevent identity conflicts
            $table->unique(['type', 'value']);

            // Optimise the two most common lookups:
            //   1. Find all credentials for a user (user management page)
            //   2. Look up a user by credential during scan
            $table->index(['user_id', 'type', 'is_active']);
            $table->index(['type', 'value', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_credentials');
    }
};
