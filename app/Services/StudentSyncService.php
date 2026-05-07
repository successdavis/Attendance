<?php

namespace App\Services;

use App\Models\StudentSyncLog;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * StudentSyncService
 *
 * Pulls active students from the external academy API and upserts them
 * into the local users table.
 *
 * External API contract (stechmax-v2):
 *   GET  {sync_api_url}/api/attendance/students
 *   Authorization: Bearer {sync_api_key}
 *
 *   Response:
 *   {
 *     "data": [
 *       {
 *         "id":                 "STM/2025/B8/0642",  // external student ID (immutable)
 *         "name":               "Full Name",
 *         "email":              "student@example.com",
 *         "phone":              "08012345678",
 *         "program":            "Certificate in Computer Operation",
 *         "program_expires_at": "2026-03-01",         // null if no expiry
 *         "enrolled_at":        "2025-08-29",
 *         "is_active":          true
 *       }
 *     ],
 *     "meta": { "total": 75, "generated_at": "..." }
 *   }
 *
 * Upsert key: external_student_id  (matched against API "id" field)
 *
 * Deactivation: if sync_deactivate_missing = true, any local user whose
 * external_student_id does NOT appear in the API response will have their
 * status set to 'inactive'.
 */
class StudentSyncService
{
    public function __construct(
        private readonly AttendanceSettingsService $settings,
    ) {}

    /**
     * Run a full student sync.
     *
     * @param  string   $triggeredBy       'schedule' | 'manual' | 'command'
     * @param  int|null $triggeredByUserId  Admin user ID for manual runs, null for automated
     * @return StudentSyncLog              The completed (or failed) log record
     */
    public function sync(string $triggeredBy = 'schedule', ?int $triggeredByUserId = null): StudentSyncLog
    {
        // 1. Open a sync log record so we can track this run
        $log = StudentSyncLog::create([
            'triggered_by'      => $triggeredBy,
            'triggered_by_user' => $triggeredByUserId,
            'started_at'        => now(),
            'status'            => 'running',
        ]);

        try {
            // 2. Read API configuration from settings
            $apiUrl = rtrim($this->settings->getString('sync_api_url'), '/');
            $apiKey = $this->settings->getString('sync_api_key');

            if (empty($apiUrl)) {
                throw new \RuntimeException('sync_api_url is not configured in attendance settings.');
            }

            // 3. Fetch all active students from the external API
            $students = $this->fetchStudents($apiUrl, $apiKey, $log);

            // 4. Upsert each student into the local users table
            $created    = 0;
            $updated    = 0;
            $skipped    = 0;
            $syncedIds  = [];

            foreach ($students as $student) {
                $externalId = $student['id'] ?? null;

                if (! $externalId) {
                    $skipped++;
                    continue;
                }

                $syncedIds[] = $externalId;

                $result = $this->upsertStudent($student);

                match ($result) {
                    'created' => $created++,
                    'updated' => $updated++,
                    default   => $skipped++,
                };
            }

            // 5. Optionally deactivate students no longer present in the API response
            $deactivated = 0;

            if ($this->settings->getBool('sync_deactivate_missing', true) && ! empty($syncedIds)) {
                $deactivated = User::whereNotNull('external_student_id')
                    ->whereNotIn('external_student_id', $syncedIds)
                    ->where('status', 'active')
                    ->update(['status' => 'inactive']);
            }

            // 6. Mark sync log as completed
            $log->update([
                'finished_at'       => now(),
                'status'            => 'completed',
                'total_fetched'     => count($students),
                'total_created'     => $created,
                'total_updated'     => $updated,
                'total_skipped'     => $skipped,
                'total_deactivated' => $deactivated,
            ]);

            Log::info('StudentSyncService: sync completed', [
                'fetched'     => count($students),
                'created'     => $created,
                'updated'     => $updated,
                'skipped'     => $skipped,
                'deactivated' => $deactivated,
            ]);
        } catch (Throwable $e) {
            Log::error('StudentSyncService: sync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $log->update([
                'finished_at'   => now(),
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }

        return $log->fresh();
    }

    // ─── Internal ────────────────────────────────────────────────────────────────

    /**
     * Fetch all students from the external API.
     * Handles both paginated responses (meta.last_page) and flat single-page responses.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchStudents(string $apiUrl, string $apiKey, StudentSyncLog $log): array
    {
        $endpoint = $apiUrl . '/api/attendance/students';
        $allStudents = [];
        $snapshot    = null;

        // Build the HTTP client — always send bearer token if key is present
        $client = Http::timeout(30)
            ->when(! empty($apiKey), fn ($h) => $h->withToken($apiKey))
            ->acceptJson();

        // First request — may be the only one if no pagination
        $response = $client->get($endpoint);

        if (! $response->successful()) {
            throw new \RuntimeException(
                "External API returned HTTP {$response->status()} from [{$endpoint}]."
            );
        }

        $body        = $response->json();
        $snapshot    = $body['meta'] ?? null;
        $allStudents = array_merge($allStudents, $body['data'] ?? []);

        // Store meta snapshot early so partial failures are still informative
        $log->update(['response_snapshot' => $snapshot]);

        // Handle paginated responses (if the API ever adds pagination)
        $lastPage = $body['meta']['last_page'] ?? 1;
        $perPage  = $body['meta']['per_page']  ?? null;

        if ($lastPage > 1 && $perPage) {
            for ($page = 2; $page <= $lastPage; $page++) {
                $response = $client->get($endpoint, [
                    'page'     => $page,
                    'per_page' => $perPage,
                ]);

                if (! $response->successful()) {
                    throw new \RuntimeException(
                        "External API returned HTTP {$response->status()} on page {$page}."
                    );
                }

                $body        = $response->json();
                $allStudents = array_merge($allStudents, $body['data'] ?? []);
            }
        }

        return $allStudents;
    }

    /**
     * Create or update a local user from the external student record.
     *
     * @param  array<string, mixed> $student
     * @return 'created'|'updated'|'skipped'
     */
    private function upsertStudent(array $student): string
    {
        $externalId = $student['id'];
        $email      = $student['email'] ?? null;

        // Shared attributes to persist on every sync
        $attributes = [
            'name'               => $student['name'] ?? 'Unknown',
            'external_synced_at' => now(),
            'status'             => 'active', // being in the active list means active
        ];

        // program_expires_at can be null (open-ended enrollment)
        if (array_key_exists('program_expires_at', $student)) {
            $attributes['program_expires_at'] = $student['program_expires_at']
                ? \Carbon\Carbon::parse($student['program_expires_at'])
                : null;
        }

        // Try to find an existing user by external_student_id first
        $existing = User::where('external_student_id', $externalId)->first();

        if ($existing) {
            // If email changed on the academy side, update it too
            if ($email && $email !== $existing->email) {
                $attributes['email'] = $email;
            }

            $existing->update($attributes);
            return 'updated';
        }

        // No existing match — check if there's a local user with the same email
        // (covers the case where an admin created the account before sync ran)
        if ($email) {
            $byEmail = User::where('email', $email)->whereNull('external_student_id')->first();

            if ($byEmail) {
                $byEmail->update(array_merge($attributes, [
                    'external_student_id' => $externalId,
                ]));

                // Assign the student Spatie role if not already set
                if (! $byEmail->hasRole('student')) {
                    $byEmail->assignRole('student');
                }

                return 'updated';
            }
        }

        // Completely new student — create a local account
        $newUser = User::create(array_merge($attributes, [
            'email'               => $email,
            'external_student_id' => $externalId,
            'role'                => 'student',
            'password'            => bcrypt(Str::random(32)), // not used for login
            'status'              => 'active',
        ]));

        // Assign Spatie role
        $newUser->assignRole('student');

        return 'created';
    }
}
