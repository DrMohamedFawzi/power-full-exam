<?php

declare(strict_types=1);

namespace App\Modules\Overwatch\Jobs;

use App\Modules\Overwatch\Actions\RecordThreat;
use App\Modules\Overwatch\Enums\AttackType;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Queued so a WAF detection never adds DB latency to the offending request
 * (or, more importantly, to the many legitimate requests the scanner runs
 * against on every single page load).
 */
final class RecordThreatJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $ipAddress,
        private readonly AttackType $attackType,
        private readonly ?string $requestPath,
        private readonly ?string $payload,
        private readonly ?string $userAgent,
        private readonly ?int $userId,
    ) {}

    public function handle(RecordThreat $recordThreat): void
    {
        $recordThreat(
            ipAddress: $this->ipAddress,
            attackType: $this->attackType,
            requestPath: $this->requestPath,
            payload: $this->payload,
            userAgent: $this->userAgent,
            userId: $this->userId,
        );
    }
}
