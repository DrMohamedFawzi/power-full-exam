<?php

declare(strict_types=1);

namespace App\Modules\Overwatch\Actions;

use App\Modules\Overwatch\Enums\AttackType;
use Illuminate\Support\Facades\Cache;

/**
 * Public contract for other modules: call this once per failed login attempt.
 *
 * Identity (or any auth surface) should call:
 *
 *     app(\App\Modules\Overwatch\Actions\RecordFailedLogin::class)($request->ip(), $identifier);
 *
 * after a credential check fails, before responding. Once an IP racks up
 * `self::THRESHOLD` failures inside `self::WINDOW_MINUTES`, a BruteForce
 * threat is logged (which itself can trigger the normal auto-ban escalation
 * in RecordThreat) and the counter resets so a legitimate user retrying does
 * not get re-flagged on every single subsequent attempt.
 */
final class RecordFailedLogin
{
    private const THRESHOLD = 5;

    private const WINDOW_MINUTES = 10;

    public function __invoke(string $ipAddress, ?string $identifier = null): void
    {
        $key = "overwatch:failed_logins:{$ipAddress}";

        $attempts = (int) Cache::get($key, 0) + 1;

        Cache::put($key, $attempts, now()->addMinutes(self::WINDOW_MINUTES));

        if ($attempts < self::THRESHOLD) {
            return;
        }

        app(RecordThreat::class)(
            ipAddress: $ipAddress,
            attackType: AttackType::BruteForce,
            requestPath: 'login',
            payload: $identifier === null ? null : "محاولات دخول فاشلة متكررة للمعرّف: {$identifier}",
        );

        Cache::forget($key);
    }
}
