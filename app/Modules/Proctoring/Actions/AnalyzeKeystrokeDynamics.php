<?php

declare(strict_types=1);

namespace App\Modules\Proctoring\Actions;

use App\Modules\Assessment\Models\ExamSession;
use App\Modules\Proctoring\Data\KeystrokeAnalysisResult;
use App\Modules\Proctoring\Models\KeystrokeSample;

/**
 * Ported from the legacy `proctoring_service.py::analyze_keystroke_dynamics`.
 * The Python worker compared dwell/flight time against fixed human-typing
 * bounds; here we compare a fresh window of key-to-key intervals against the
 * session's OWN prior samples, which catches an impersonator mid-exam even if
 * their typing is individually "normal" — a sharp change from the baseline is
 * itself the signal.
 */
final class AnalyzeKeystrokeDynamics
{
    private const int MIN_SAMPLE_SIZE = 5;

    private const float ANOMALY_THRESHOLD = 2.5;

    private const float MIN_BASELINE_STD_MS = 5.0;

    private const int BASELINE_WINDOW = 5;

    /** @param  list<float>  $intervalsMs  Milliseconds between consecutive keystrokes. */
    public function __invoke(ExamSession $session, array $intervalsMs): KeystrokeAnalysisResult
    {
        $intervalsMs = array_values(array_filter($intervalsMs, static fn (mixed $v): bool => is_numeric($v)));
        $n = count($intervalsMs);

        [$mean, $std] = $this->stats($intervalsMs, $n);

        $baseline = KeystrokeSample::query()
            ->where('exam_session_id', $session->id)
            ->orderByDesc('captured_at')
            ->limit(self::BASELINE_WINDOW)
            ->get();

        $anomalyScore = 0.0;
        $isAnomalous = false;

        if ($n >= self::MIN_SAMPLE_SIZE && $baseline->isNotEmpty()) {
            $baselineMean = (float) $baseline->avg('mean_interval_ms');
            $baselineStd = max((float) $baseline->avg('std_deviation_ms'), self::MIN_BASELINE_STD_MS);

            $anomalyScore = abs($mean - $baselineMean) / $baselineStd;
            $isAnomalous = $anomalyScore >= self::ANOMALY_THRESHOLD;
        }

        $sample = KeystrokeSample::query()->create([
            'exam_session_id' => $session->id,
            'mean_interval_ms' => round($mean, 2),
            'std_deviation_ms' => round($std, 2),
            'sample_size' => $n,
            'anomaly_score' => round($anomalyScore, 2),
            'captured_at' => now(),
        ]);

        return new KeystrokeAnalysisResult($sample, $isAnomalous);
    }

    /**
     * @param  list<float>  $intervalsMs
     * @return array{0: float, 1: float}
     */
    private function stats(array $intervalsMs, int $n): array
    {
        if ($n === 0) {
            return [0.0, 0.0];
        }

        $mean = array_sum($intervalsMs) / $n;

        if ($n < 2) {
            return [$mean, 0.0];
        }

        $variance = array_sum(array_map(
            static fn (float $v): float => ($v - $mean) ** 2,
            $intervalsMs,
        )) / ($n - 1);

        return [$mean, sqrt($variance)];
    }
}
