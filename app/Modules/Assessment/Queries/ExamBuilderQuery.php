<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Queries;

use App\Modules\Assessment\Models\Exam;

/**
 * Shapes an exam + its questions for the teacher-facing builder. Unlike any
 * student-facing payload, this one deliberately includes `correct_answer` and
 * `explanation` — the teacher authored them and must be able to edit them.
 */
final class ExamBuilderQuery
{
    /** @return array<string, mixed> */
    public function __invoke(Exam $exam): array
    {
        $exam->loadCount('sessions');

        return [
            'id' => $exam->id,
            'code' => $exam->code,
            'title' => $exam->title,
            'status' => $exam->status,
            'locked' => $exam->sessions_count > 0,
            'questions' => $exam->questions->map(static fn ($question): array => [
                'id' => $question->id,
                'position' => $question->position,
                'type' => $question->type->value,
                'type_label' => $question->type->label(),
                'prompt' => $question->prompt,
                'options' => $question->options ?? [],
                'correct_answer' => $question->correct_answer ?? [],
                'explanation' => $question->explanation,
                'points' => (float) $question->points,
            ])->values()->all(),
        ];
    }
}
