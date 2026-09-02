<?php

declare(strict_types=1);

namespace App\Modules\Proctoring\Services;

use App\Modules\Proctoring\Models\Heartbeat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * Redis-backed buffer for Heartbeat writes.
 *
 * ┌─────────────────────────────────────────────────────────────┐
 * │  WRITE PATH (hot — called on every heartbeat beacon)        │
 * │  ─────────────────────────────────────────────────────────  │
 * │  push() → RPUSH heartbeat:buffer {json}                     │
 * │  Returns in < 1ms. NO DB write on the request cycle.        │
 * │                                                             │
 * │  READ PATH (cold — runs every second via Artisan schedule)  │
 * │  ─────────────────────────────────────────────────────────  │
 * │  flush() → LRANGE + LTRIM → DB::table()->insert([...])      │
 * │  Single Bulk INSERT regardless of how many students sent     │
 * │  heartbeats in that window (could be 50,000).               │
 * └─────────────────────────────────────────────────────────────┘
 */
final class HeartbeatBufferService
{
    private const REDIS_KEY   = 'heartbeat:buffer';
    private const CHUNK_SIZE  = 5000; // rows per Bulk INSERT

    /** @param array<string, mixed> $payload */
    public function push(array $payload): void
    {
        Redis::rpush(self::REDIS_KEY, json_encode($payload, JSON_THROW_ON_ERROR));
    }

    /**
     * Drain the Redis buffer and bulk-insert into the DB.
     * Safe to call concurrently — uses LRANGE+LTRIM (atomic-enough for this use).
     *
     * @return int Number of rows inserted
     */
    public function flush(): int
    {
        $raw = Redis::lrange(self::REDIS_KEY, 0, -1);

        if (empty($raw)) {
            return 0;
        }

        // Atomically remove the items we just read
        Redis::ltrim(self::REDIS_KEY, count($raw), -1);

        $rows = array_map(
            static fn (string $json): array => json_decode($json, true, 512, JSON_THROW_ON_ERROR),
            $raw
        );

        // Bulk insert in chunks to avoid exceeding DB max_allowed_packet
        $total = 0;
        foreach (array_chunk($rows, self::CHUNK_SIZE) as $chunk) {
            DB::table((new Heartbeat)->getTable())->insert($chunk);
            $total += count($chunk);
        }

        return $total;
    }
}
