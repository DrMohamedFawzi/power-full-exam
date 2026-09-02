<?php

declare(strict_types=1);

namespace App\Modules\Proctoring\Console;

use App\Modules\Proctoring\Services\HeartbeatBufferService;
use Illuminate\Console\Command;

/**
 * Drains the Redis heartbeat buffer and bulk-inserts into the DB.
 *
 * Registered in the scheduler to run every minute (Laravel's minimum).
 * For sub-minute execution (every second), use the Octane tick or a
 * dedicated Horizon worker pointing at the `heartbeats` queue instead.
 *
 * Usage:
 *   php artisan proctoring:flush-heartbeats
 */
final class FlushHeartbeatsCommand extends Command
{
    protected $signature   = 'proctoring:flush-heartbeats';
    protected $description = 'Drain the Redis heartbeat buffer and bulk-insert into the database';

    public function handle(HeartbeatBufferService $buffer): int
    {
        $inserted = $buffer->flush();

        if ($inserted > 0) {
            $this->info("[HeartbeatBuffer] Flushed {$inserted} rows.");
        }

        return self::SUCCESS;
    }
}
