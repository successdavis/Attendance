<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendance_policies', function (Blueprint $table) {
            $table->id();

            $table->string('name', 150);
            $table->text('description')->nullable();

            // Time windows — null means "inherit from global attendance_settings"
            $table->time('sign_in_start')->nullable();
            $table->time('sign_in_end')->nullable();
            $table->time('sign_out_start')->nullable();
            $table->time('sign_out_end')->nullable();

            // Behaviour overrides — null means "inherit from global settings"
            $table->unsignedSmallInteger('grace_period_minutes')->nullable();
            $table->unsignedSmallInteger('late_threshold_minutes')->nullable();
            $table->boolean('allow_multiple_daily')->nullable();
            $table->boolean('weekend_allowed')->nullable();
            $table->boolean('time_restriction_enabled')->nullable();

            // A policy marked as default is applied to users with no explicit policy_id
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_policies');
    }
};
