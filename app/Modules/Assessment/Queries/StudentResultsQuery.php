<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Queries;

use App\Modules\Assessment\Models\ExamSession;
use App\Modules\Identity\Models\User;

/** The student's own finished attempts. */
final class StudentResultsQuery
{
    /** @return array<int, array<string, mixed>> */
    public function __invoke(User $student): array
    {
        return ExamSession::query()
            ->with('exam')
            ->where('student_id', $student->id)
            ->whereIn('status', ['submitted', 'expired', 'terminated'])
            ->latest('submitted_at')
            ->get()
            ->map(fn (ExamSession $session): array => [
                'id' => $session->id,
                'exam_title' => $session->exam->title,
                'status' => ['value' => $session->status->value, 'label' => $session->status->label(), 'color' => $session->status->color()],
                'score' => $session->score !== null ? (float) $session->score : null,
                'integrity_index' => $session->integrity_index,
                'submitted_at' => $session->submitted_at?->toIso8601String(),
                'submitted_at_label' => $session->submitted_at?->translatedFormat('Y/m/d H:i') ?? '—',
            ])
            ->all();
    }
}
