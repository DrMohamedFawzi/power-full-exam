<?php

declare(strict_types=1);

namespace App\Modules\Overwatch\Actions;

use App\Modules\Overwatch\Enums\AttackType;
use App\Modules\Overwatch\Models\Threat;
use Illuminate\Support\Facades\Config;

/**
 * Persists a single threat detection and, once an IP has accumulated enough
 * detections in the recent window, escalates to an automatic ban.
 *
 * This is the one place that writes to `threats`, so both the queued WAF path
 * and the synchronous rate-limit / brute-force paths share the same rule.
 */
final class RecordThreat
{
    public function __invoke(
        string $ipAddress,
        AttackType $attackType,
        ?string $requestPath = null,
        ?string $payload = null,
        ?string $userAgent = null,
        ?int $userId = null,
    ): Threat {
        $threat = Threat::create([
            'ip_address' => $ipAddress,
            'user_id' => $userId,
            'attack_type' => $attackType,
            'severity' => $attackType->severity(),
            'request_path' => $requestPath === null ? null : mb_substr($requestPath, 0, 255),
            'payload' => $payload === null ? null : mb_substr($payload, 0, 2000),
            'user_agent' => $userAgent === null ? null : mb_substr($userAgent, 0, 1000),
            'detected_at' => now(),
        ]);

        $this->autoBanIfThresholdReached($ipAddress);

        return $threat;
    }

    private function autoBanIfThresholdReached(string $ipAddress): void
    {
        $threshold = (int) Config::get('aegis.overwatch.auto_ban_threshold');

        if ($threshold <= 0) {
            return;
        }

        $recentDetections = Threat::query()
            ->where('ip_address', $ipAddress)
            ->where('detected_at', '>=', now()->subHour())
            ->count();

        if ($recentDetections < $threshold) {
            return;
        }

        app(BanIp::class)(
            ipAddress: $ipAddress,
            reason: 'حظر تلقائي: تجاوز عدد التهديدات المسموح به',
            minutes: (int) Config::get('aegis.overwatch.auto_ban_minutes'),
        );
    }
}
