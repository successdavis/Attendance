<?php

namespace App\Console\Commands;

use App\Services\StudentSyncService;
use Illuminate\Console\Command;

/**
 * Artisan command: php artisan students:sync
 *
 * Runs the student sync inline (not via queue) so you can see live output.
 * Useful for manual server-side runs and scheduled task debugging.
 *
 * Options:
 *   --user=ID   Associate the sync log with an admin user ID (optional)
 */
class SyncStudentsCommand extends Command
{
    protected $signature = 'students:sync {--user= : Admin user ID to associate with the sync log}';

    protected $description = 'Pull active students from the external academy API and sync them into the local database';

    public function handle(StudentSyncService $service): int
    {
        $userId = $this->option('user') ? (int) $this->option('user') : null;

        $this->info('Starting student sync...');
        $this->newLine();

        $log = $service->sync('command', $userId);

        if ($log->status === 'failed') {
            $this->error('Sync failed: ' . $log->error_message);
            return self::FAILURE;
        }

        $duration = $log->started_at && $log->finished_at
            ? $log->started_at->diffInSeconds($log->finished_at) . 's'
            : '–';

        $this->table(
            ['Metric', 'Count'],
            [
                ['Status',       ucfirst($log->status)],
                ['Duration',     $duration],
                ['Fetched',      $log->total_fetched],
                ['Created',      $log->total_created],
                ['Updated',      $log->total_updated],
                ['Deactivated',  $log->total_deactivated],
                ['Skipped',      $log->total_skipped],
            ]
        );

        if ($log->status === 'partial') {
            $this->warn('Sync completed with warnings: ' . $log->error_message);
            return self::FAILURE;
        }

        $this->info('Sync completed successfully.');

        return self::SUCCESS;
    }
}
