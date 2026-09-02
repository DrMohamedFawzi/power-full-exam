<?php

declare(strict_types=1);

namespace App\Modules\Overwatch\Queries;

use App\Modules\Overwatch\Models\BannedIp;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * The ban list: active and expired, most recent first, with who banned whom.
 */
final class BansQuery
{
    private const PER_PAGE = 20;

    public function __invoke(): LengthAwarePaginator
    {
        $paginator = BannedIp::query()
            ->with('bannedBy')
            ->latest('created_at')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $paginator->getCollection()->transform(fn (BannedIp $ban): array => [
            'id' => $ban->id,
            'ip_address' => $ban->ip_address,
            'reason' => $ban->reason,
            'banned_by_name' => $ban->bannedBy?->official_name ?? 'تلقائي',
            'banned_at' => $ban->created_at?->toDateTimeString(),
            'banned_until' => $ban->banned_until?->toDateTimeString(),
            'is_permanent' => $ban->banned_until === null,
            'is_active' => $ban->banned_until === null || $ban->banned_until->isFuture(),
        ]);

        return $paginator;
    }
}
