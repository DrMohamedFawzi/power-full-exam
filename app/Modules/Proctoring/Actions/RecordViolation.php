<?php

declare(strict_types=1);

namespace App\Modules\Proctoring\Actions;

use App\Modules\Assessment\Models\ExamSession;
use App\Modules\Proctoring\Data\ViolationClassification;
use App\Modules\Proctoring\Models\Violation;

/**
 * Persists one classified violation. Pure write — the caller decides whether
 * the integrity index needs recalculating afterwards.
 */
final class RecordViolation
{
    /** @param  array<string, mixed>|null  $metadata */
    public function __invoke(
        ExamSession $session,
        ViolationClassification $classification,
        ?string $details = null,
        ?array $metadata = null,
    ): Violation {
        return Violation::query()->create([
            'exam_session_id' => $session->id,
            'student_id' => $session->student_id,
            'type' => $classification->type->value,
            'severity' => $classification->severity->value,
            'penalty' => $classification->penalty,
            'details' => $details,
            'metadata' => $metadata,
            'detected_at' => now(),
        ]);
    }
}
