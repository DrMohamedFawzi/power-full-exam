<?php

declare(strict_types=1);

namespace App\Modules\Overwatch\Actions;

use App\Modules\Overwatch\Models\BannedIp;
use Illuminate\Support\Facades\Cache;

/**
 * Lifts a ban immediately: deletes the row and busts the ShieldRequest cache.
 */
final class UnbanIp
{
    public function __invoke(BannedIp $ban): void
    {
        $ipAddress = $ban->ip_address;

        $ban->delete();

        Cache::forget("overwatch:banned:{$ipAddress}");
    }
}
