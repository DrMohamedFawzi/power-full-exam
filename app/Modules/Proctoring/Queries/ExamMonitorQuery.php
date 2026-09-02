<?php

declare(strict_types=1);

namespace App\Modules\Proctoring\Queries;

use App\Modules\Assessment\Models\Exam;
use App\Modules\Assessment\Models\ExamSession;

/**
 * Live snapshot of one exam's active sessions: student, progress, integrity,
 * connectivity, and the most recent violations. Polled every few seconds by
 * the teacher's monitor page — no websockets.
 */
final class ExamMonitorQuery
{
    /** @return array<int, array<string, mixed>> */
    public function __invoke(Exam $exam): array
    {
        $totalQuestions = $exam->questions()->count();

        return ExamSession::query()
            ->where('exam_id', $exam->id)
            ->active()
            ->with(['student', 'answers', 'violations' => fn ($q) => $q->latest('detected_at')->limit(5)])
            ->get()
            ->map(fn (ExamSession $session): array => [
                'id' => $session->id,
                'student_name' => $session->student->official_name,
                'answered_count' => $session->answers->count(),
                'total_questions' => $totalQuestions,
                'integrity_index' => $session->integrity_index,
                'offline_seconds' => $session->offline_seconds,
                'started_at' => $session->started_at?->toIso8601String(),
                'expires_at' => $session->expires_at?->toIso8601String(),
                'recent_violations' => $session->violations->map(fn ($violation): array => [
                    'label' => $violation->type->label(),
                    'severity' => ['label' => $violation->severity->label(), 'color' => $violation->severity->color()],
                    'detected_at' => $violation->detected_at?->toIso8601String(),
                ])->all(),
            ])
            ->all();
    }
}
