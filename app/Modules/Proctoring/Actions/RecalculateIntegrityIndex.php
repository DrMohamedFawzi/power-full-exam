<?php

declare(strict_types=1);

namespace App\Modules\Proctoring\Actions;

use App\Modules\Assessment\Actions\TerminateExamSession;
use App\Modules\Assessment\Enums\SecurityLevel;
use App\Modules\Assessment\Enums\SessionStatus;
use App\Modules\Assessment\Models\ExamSession;
use App\Modules\Proctoring\Models\Violation;

/**
 * The integrity index is derived, never incremented in place: recompute it
 * from the session's full violation set every time, so it is always
 * reproducible from the data alone. Auto-terminates a strict-security session
 * that has fallen below the configured threshold.
 */
final class RecalculateIntegrityIndex
{
    public function __invoke(ExamSession $session): int
    {
        $penalties = (int) Violation::query()
            ->where('exam_session_id', $session->id)
            ->sum('penalty');

        $index = max(0, 100 - $penalties);

        $session->integrity_index = $index;
        $session->save();

        $this->terminateIfBeyondThreshold($session, $index);

        return $index;
    }

    private function terminateIfBeyondThreshold(ExamSession $session, int $index): void
    {
        if ($session->status !== SessionStatus::Active) {
            return;
        }

        if ($session->exam->security_level !== SecurityLevel::Strict) {
            return;
        }

        if ($index >= (int) config('aegis.proctoring.auto_submit_threshold')) {
            return;
        }

        app(TerminateExamSession::class)($session);
    }
}
