<?php

declare(strict_types=1);

namespace App\Modules\Overwatch\Actions;

use App\Modules\Overwatch\Models\BannedIp;
use Illuminate\Support\Facades\Cache;

/**
 * Creates or refreshes a ban for an IP address, manual or automatic.
 *
 * Busts the ShieldRequest ban-lookup cache so the ban takes effect on the very
 * next request, not after the cache TTL expires.
 */
final class BanIp
{
    public function __invoke(
        string $ipAddress,
        ?string $reason = null,
        ?int $minutes = null,
        ?int $bannedByUserId = null,
    ): BannedIp {
        $ban = BannedIp::updateOrCreate(
            ['ip_address' => $ipAddress],
            [
                'reason' => $reason,
                'banned_by' => $bannedByUserId,
                'banned_until' => $minutes === null || $minutes <= 0 ? null : now()->addMinutes($minutes),
            ],
        );

        Cache::forget("overwatch:banned:{$ipAddress}");

        return $ban;
    }
}
