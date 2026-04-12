<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Copy existing rfid_uid / face_template_id / fingerprint_template_id from the users table
     * into the new user_credentials table, then drop those columns from users.
     *
     * This is safe to run even on an empty database — it simply won't insert anything.
     */
    public function up(): void
    {
        // Migrate RFID UIDs
        DB::table('users')
            ->whereNotNull('rfid_uid')
            ->chunkById(100, function ($users) {
                $records = $users->map(fn ($u) => [
                    'user_id'     => $u->id,
                    'type'        => 'rfid',
                    'value'       => $u->rfid_uid,
                    'is_active'   => true,
                    'enrolled_at' => $u->created_at,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ])->toArray();

                DB::table('user_credentials')->insertOrIgnore($records);
            });

        // Migrate face template IDs
        DB::table('users')
            ->whereNotNull('face_template_id')
            ->chunkById(100, function ($users) {
                $records = $users->map(fn ($u) => [
                    'user_id'     => $u->id,
                    'type'        => 'face',
                    'value'       => $u->face_template_id,
                    'is_active'   => true,
                    'enrolled_at' => $u->created_at,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ])->toArray();

                DB::table('user_credentials')->insertOrIgnore($records);
            });

        // Migrate fingerprint template IDs
        DB::table('users')
            ->whereNotNull('fingerprint_template_id')
            ->chunkById(100, function ($users) {
                $records = $users->map(fn ($u) => [
                    'user_id'          => $u->id,
                    'type'             => 'fingerprint',
                    'value'            => $u->fingerprint_template_id,
                    'is_active'        => true,
                    'enrolled_at'      => $u->created_at,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ])->toArray();

                DB::table('user_credentials')->insertOrIgnore($records);
            });

        // Drop the now-redundant columns from users
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_rfid_uid_unique');
            $table->dropUnique('users_face_template_id_unique');
            $table->dropUnique('users_fingerprint_template_id_unique');
            $table->dropColumn(['rfid_uid', 'face_template_id', 'fingerprint_template_id']);
        });
    }

    public function down(): void
    {
        // Re-add the columns (data is not restored — this is a destructive migration)
        Schema::table('users', function (Blueprint $table) {
            $table->string('rfid_uid')->nullable()->unique();
            $table->string('face_template_id')->nullable()->unique();
            $table->string('fingerprint_template_id')->nullable()->unique();
        });
    }
};
