<?php

declare(strict_types=1);

namespace App\Modules\Overwatch\Listeners;

use App\Modules\Overwatch\Actions\RecordFailedLogin;
use Illuminate\Auth\Events\Failed;

/**
 * Brute-force detection, wired the only way the dependency rule allows.
 *
 * Identity may depend on nothing, so it cannot call into Overwatch to report a
 * failed credential check. Instead Overwatch — which sits above Identity and is
 * allowed to know about it — listens for the framework's own auth-failure event.
 * Identity stays completely unaware that Overwatch exists.
 */
final class RecordFailedLoginAttempt
{
    public function __construct(private readonly RecordFailedLogin $recordFailedLogin) {}

    public function handle(Failed $event): void
    {
        ($this->recordFailedLogin)(
            request()->ip() ?? '0.0.0.0',
            $event->credentials['email'] ?? null,
        );
    }
}
