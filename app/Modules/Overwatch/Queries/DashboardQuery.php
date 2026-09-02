<?php

declare(strict_types=1);

namespace App\Modules\Overwatch\Queries;

use App\Modules\Overwatch\Enums\AttackType;
use App\Modules\Overwatch\Models\BannedIp;
use App\Modules\Overwatch\Models\Threat;

/**
 * Shapes everything the threat console dashboard renders: four KPI tiles, a
 * daily time-series for the chart, and the most recent detections.
 */
final class DashboardQuery
{
    private const CHART_DAYS = 14;

    private const RECENT_LIMIT = 10;

    /**
     * @return array{
     *     kpis: array{
     *         threats_today: int,
     *         blocked_ips: int,
     *         top_attack_type: array{label: string, color: string}|null,
     *         requests_rejected_today: int,
     *     },
     *     chart: array{labels: list<string>, data: list<int>},
     *     recent_threats: list<array<string, mixed>>,
     * }
     */
    public function __invoke(): array
    {
        return [
            'kpis' => [
                'threats_today' => Threat::query()->whereDate('detected_at', today())->count(),
                'blocked_ips' => BannedIp::query()->active()->count(),
                'top_attack_type' => $this->topAttackType(),
                // Only rate-limit rejections are logged as a distinct event at the
                // moment of rejection (a WAF hit logs a detection but the request
                // itself is still served, per ShieldRequest's design); this is the
                // most accurate proxy we have for "requests actually rejected".
                'requests_rejected_today' => Threat::query()
                    ->whereDate('detected_at', today())
                    ->where('attack_type', AttackType::RateAbuse->value)
                    ->count(),
            ],
            'chart' => $this->chartSeries(),
            'recent_threats' => $this->recentThreats(),
        ];
    }

    private function topAttackType(): ?array
    {
        $row = Threat::query()
            ->selectRaw('attack_type, count(*) as total')
            ->where('detected_at', '>=', now()->subDays(7))
            ->groupBy('attack_type')
            ->orderByDesc('total')
            ->first();

        if ($row === null) {
            return null;
        }

        // Eloquent already casts `attack_type` to the enum via Threat's casts(),
        // even on a selectRaw() aggregate row, so it must not be re-parsed with from().
        $type = $row->attack_type instanceof AttackType
            ? $row->attack_type
            : AttackType::from($row->attack_type);

        return ['label' => $type->label(), 'color' => $type->color()];
    }

    /** @return array{labels: list<string>, data: list<int>} */
    private function chartSeries(): array
    {
        $start = today()->subDays(self::CHART_DAYS - 1);

        $counts = Threat::query()
            ->selectRaw('DATE(detected_at) as day, count(*) as total')
            ->where('detected_at', '>=', $start)
            ->groupBy('day')
            ->pluck('total', 'day');

        $labels = [];
        $data = [];

        for ($day = $start->copy(); $day->lte(today()); $day->addDay()) {
            $key = $day->toDateString();
            $labels[] = $day->translatedFormat('d M');
            $data[] = (int) ($counts[$key] ?? 0);
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /** @return list<array<string, mixed>> */
    private function recentThreats(): array
    {
        return Threat::query()
            ->latest('detected_at')
            ->limit(self::RECENT_LIMIT)
            ->get()
            ->map(fn (Threat $threat): array => [
                'id' => $threat->id,
                'ip_address' => $threat->ip_address,
                'attack_type_label' => $threat->attack_type->label(),
                'attack_type_color' => $threat->attack_type->color(),
                'severity_label' => $threat->severity->label(),
                'severity_color' => $threat->severity->color(),
                'request_path' => $threat->request_path,
                'detected_at' => $threat->detected_at?->diffForHumans(),
                'detected_at_full' => $threat->detected_at?->toDateTimeString(),
            ])
            ->all();
    }
}
