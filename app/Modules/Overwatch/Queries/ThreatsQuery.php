<?php

declare(strict_types=1);

namespace App\Modules\Overwatch\Queries;

use App\Modules\Overwatch\Models\Threat;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * The filterable, paginated threat log. Returns a paginator whose items have
 * already been reduced to plain arrays — the view never touches Eloquent.
 */
final class ThreatsQuery
{
    private const PER_PAGE = 20;

    /**
     * @param  array{
     *     attack_type?: string|null,
     *     severity?: string|null,
     *     ip_address?: string|null,
     *     date_from?: string|null,
     *     date_to?: string|null,
     * }  $filters
     */
    public function __invoke(array $filters): LengthAwarePaginator
    {
        $paginator = Threat::query()
            ->when($filters['attack_type'] ?? null, fn ($query, $type) => $query->where('attack_type', $type))
            ->when($filters['severity'] ?? null, fn ($query, $severity) => $query->where('severity', $severity))
            ->when($filters['ip_address'] ?? null, fn ($query, $ip) => $query->where('ip_address', 'like', "%{$ip}%"))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('detected_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('detected_at', '<=', $date))
            ->latest('detected_at')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $paginator->getCollection()->transform(fn (Threat $threat): array => [
            'id' => $threat->id,
            'ip_address' => $threat->ip_address,
            'attack_type_label' => $threat->attack_type->label(),
            'attack_type_color' => $threat->attack_type->color(),
            'severity_label' => $threat->severity->label(),
            'severity_color' => $threat->severity->color(),
            'request_path' => $threat->request_path,
            'payload' => $threat->payload,
            'user_agent' => $threat->user_agent,
            'detected_at' => $threat->detected_at?->toDateTimeString(),
        ]);

        return $paginator;
    }
}
