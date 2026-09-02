<?php

declare(strict_types=1);

namespace App\Modules\Proctoring\Actions;

use App\Modules\Assessment\Models\ExamSession;
use App\Modules\Proctoring\Data\ViolationClassification;
use App\Modules\Proctoring\Enums\ViolationType;
use App\Modules\Proctoring\Models\Violation;
use App\Support\Enums\Severity;

/**
 * Maps a violation type to its severity/penalty, escalating one severity level
 * for every three prior occurrences of the same type in the session — a
 * student who keeps tripping the same signal is more likely to be cheating,
 * not unlucky.
 */
final class ClassifyViolation
{
    private const array ORDER = [Severity::Low, Severity::Medium, Severity::High, Severity::Critical];

    private const int ESCALATE_EVERY = 3;

    public function __invoke(ExamSession $session, ViolationType $type): ViolationClassification
    {
        $priorCount = Violation::query()
            ->where('exam_session_id', $session->id)
            ->where('type', $type->value)
            ->count();

        $baseIndex = array_search($type->severity(), self::ORDER, true);
        $escalations = intdiv($priorCount, self::ESCALATE_EVERY);
        $severity = self::ORDER[min($baseIndex + $escalations, count(self::ORDER) - 1)];

        return new ViolationClassification($type, $severity, $severity->penalty());
    }
}
