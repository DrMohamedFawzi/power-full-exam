<?php

declare(strict_types=1);

namespace App\Modules\Overwatch\Console\Commands;

use App\Modules\Overwatch\Models\BannedIp;
use App\Modules\Overwatch\Models\Threat;
use Illuminate\Console\Command;

/**
 * Housekeeping for the Overwatch tables: old threats bloat the log without
 * adding value, and expired bans have already been superseded by the
 * `active()` scope — this just reclaims the storage.
 */
final class PruneOverwatch extends Command
{
    protected $signature = 'overwatch:prune {--days=90 : Delete threats older than this many days}';

    protected $description = 'Delete threats older than N days and bans that have already expired';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        $deletedThreats = Threat::query()
            ->where('detected_at', '<', now()->subDays($days))
            ->delete();

        $deletedBans = BannedIp::query()
            ->whereNotNull('banned_until')
            ->where('banned_until', '<', now())
            ->delete();

        $this->info("Deleted {$deletedThreats} threat(s) older than {$days} days.");
        $this->info("Deleted {$deletedBans} expired ban(s).");

        return self::SUCCESS;
    }
}
