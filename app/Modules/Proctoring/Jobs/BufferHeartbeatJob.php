<?php

declare(strict_types=1);

namespace App\Modules\Proctoring\Jobs;

use App\Modules\Proctoring\Services\HeartbeatBufferService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Queued job that pushes a heartbeat beacon into the Redis buffer.
 *
 * Dispatched immediately by the proctoring ingest endpoint.
 * A separate scheduler (FlushHeartbeatsCommand) drains the buffer
 * every minute via a single Bulk INSERT.
 *
 * @see HeartbeatBufferService
 * @see FlushHeartbeatsCommand
 */
final class BufferHeartbeatJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @param array<string, mixed> $payload */
    public function __construct(
        private readonly array $payload,
    ) {
        $this->onQueue('heartbeats');
    }

    public function handle(HeartbeatBufferService $buffer): void
    {
        $buffer->push($this->payload);
    }
}
