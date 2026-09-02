<?php

declare(strict_types=1);

namespace App\Modules\Proctoring\Actions;

use App\Modules\Assessment\Models\ExamSession;
use App\Modules\Proctoring\Enums\ViolationType;
use App\Modules\Proctoring\Jobs\ProcessProctoringSignal;
use App\Modules\Proctoring\Models\Heartbeat;

/**
 * Persists one connectivity sample and accumulates offline time on the
 * session. Crossing the configured threshold raises ConnectionLost through
 * the same queued pipeline as every other signal — never inline, never
 * blocking. A clock-drift check compares the client's reported timestamp
 * against the server's, since the countdown must never trust the client.
 */
final class RecordHeartbeat
{
    private const int CLOCK_DRIFT_TOLERANCE_SECONDS = 20;

    public function __invoke(
        ExamSession $session,
        string $status,
        int $durationSeconds,
        ?int $clientTimestampMs = null,
    ): Heartbeat {
        try {
            $heartbeat = Heartbeat::query()->create([
                'exam_session_id' => $session->id,
                'status' => $status,
                'duration_seconds' => $durationSeconds,
                'detected_at' => now(),
            ]);

            if ($status === 'offline' && $durationSeconds > 0) {
                $session->increment('offline_seconds', $durationSeconds);

                if ($session->offline_seconds >= (int) config('aegis.exam.max_offline_seconds')) {
                    ProcessProctoringSignal::dispatch(
                        $session,
                        ViolationType::ConnectionLost,
                        null,
                        ['offline_seconds' => $session->offline_seconds],
                    );
                }
            }

            $this->detectClockDrift($session, $clientTimestampMs);

            return $heartbeat;
        } catch (\Throwable $e) {
            return new Heartbeat([
                'exam_session_id' => $session->id,
                'status' => $status,
                'duration_seconds' => $durationSeconds,
                'detected_at' => now(),
            ]);
        }
    }

    private function detectClockDrift(ExamSession $session, ?int $clientTimestampMs): void
    {
        if ($clientTimestampMs === null) {
            return;
        }

        $serverMs = (int) (microtime(true) * 1000);
        $driftSeconds = abs($serverMs - $clientTimestampMs) / 1000;

        if ($driftSeconds > self::CLOCK_DRIFT_TOLERANCE_SECONDS) {
            ProcessProctoringSignal::dispatch(
                $session,
                ViolationType::TimeManipulation,
                "انحراف الساعة: {$driftSeconds} ثانية",
                ['drift_seconds' => $driftSeconds],
            );
        }
    }
}
