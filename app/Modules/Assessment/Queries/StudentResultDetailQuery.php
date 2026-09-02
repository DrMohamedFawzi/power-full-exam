<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Queries;

use App\Modules\Assessment\Models\ExamAnswer;
use App\Modules\Assessment\Models\ExamSession;

/**
 * A submitted session's review: which answers were wrong, and why. Correct
 * answers only ever appear here, after `SessionStatus::isFinished()`.
 */
final class StudentResultDetailQuery
{
    /** @return array<string, mixed> */
    public function __invoke(ExamSession $session): array
    {
        $answers = $session->answers()->with('question')->get();

        return [
            'id' => $session->id,
            'exam_title' => $session->exam->title,
            'status' => ['value' => $session->status->value, 'label' => $session->status->label(), 'color' => $session->status->color()],
            'score' => $session->score !== null ? (float) $session->score : null,
            'integrity_index' => $session->integrity_index,
            'submitted_at' => $session->submitted_at?->toIso8601String(),
            'violation_count' => $session->violations()->count(),
            'answers' => $answers->map(fn (ExamAnswer $answer): array => [
                'prompt' => $answer->question->prompt,
                'type_label' => $answer->question->type->label(),
                'given_answer' => $answer->answer,
                'correct_answer' => $session->status->isFinished() ? $answer->question->correct_answer : null,
                'is_correct' => $answer->is_correct,
                'points_awarded' => (float) $answer->points_awarded,
                'points_possible' => (float) $answer->question->points,
                'needs_manual_grading' => ! $answer->question->type->isAutoGradable(),
            ])->all(),
        ];
    }
}
