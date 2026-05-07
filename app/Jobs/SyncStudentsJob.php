<?php

namespace App\Jobs;

use App\Services\StudentSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * SyncStudentsJob
 *
 * Queued wrapper around StudentSyncService::sync().
 *
 * Dispatch examples:
 *   // Manual trigger from admin UI (attaches user ID to sync log):
 *   SyncStudentsJob::dispatch('manual', $request->user()->id);
 *
 *   // Scheduled run (no user context):
 *   SyncStudentsJob::dispatch('schedule');
 *
 *   // CLI run:
 *   SyncStudentsJob::dispatch('command');
 *
 * The job is placed on the 'sync' queue so it doesn't block other jobs.
 * Configure the queue worker: php artisan queue:work --queue=sync,default
 */
class SyncStudentsJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    /**
     * Maximum number of retry attempts before the job is marked as failed.
     */
    public int $tries = 3;

    /**
     * Number of seconds to wait before retrying after a failure.
     */
    public int $backoff = 60;

    /**
     * Maximum seconds the job is allowed to run (5 minutes).
     */
    public int $timeout = 300;

    public function __construct(
        public readonly string $triggeredBy = 'schedule',
        public readonly ?int $triggeredByUserId = null,
    ) {
        $this->onQueue('sync');
    }

    public function handle(StudentSyncService $service): void
    {
        $service->sync($this->triggeredBy, $this->triggeredByUserId);
    }
}
