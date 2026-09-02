<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Queries;

use App\Modules\Assessment\Enums\QuestionType;
use App\Modules\Assessment\Models\Exam;
use App\Modules\Assessment\Models\ExamAnswer;

/** Short-answer responses on an exam still waiting on a teacher's grade. */
final class PendingGradingQuery
{
    /** @return list<array<string, mixed>> */
    public function __invoke(Exam $exam): array
    {
        return ExamAnswer::query()
            ->whereHas('question', fn ($q) => $q->where('exam_id', $exam->id)->where('type', QuestionType::ShortAnswer->value))
            ->whereNull('is_correct')
            ->with(['question:id,prompt,points,correct_answer', 'session.student:id,official_name'])
            ->get()
            ->map(fn (ExamAnswer $answer): array => [
                'id' => $answer->id,
                'student_name' => $answer->session->student->official_name,
                'prompt' => $answer->question->prompt,
                'reference_answer' => $answer->question->correct_answer[0] ?? null,
                'submitted_answer' => $answer->answer[0] ?? null,
                'max_points' => (float) $answer->question->points,
            ])
            ->all();
    }
}
