<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Queries;

use App\Modules\Assessment\Enums\ExamStatus;
use App\Modules\Assessment\Models\Exam;

/** Shapes a single exam for the read-only teacher overview (`show`) page. */
final class ExamOverviewQuery
{
    /** @return array<string, mixed> */
    public function __invoke(Exam $exam): array
    {
        $exam->loadCount(['questions', 'sessions']);

        return [
            'id' => $exam->id,
            'title' => $exam->title,
            'description' => $exam->description,
            'code' => $exam->code,
            'status' => $exam->status,
            'mode' => $exam->mode,
            'security_level' => $exam->security_level,
            'duration_minutes' => $exam->duration_minutes,
            'max_attempts' => $exam->max_attempts,
            'classroom_name' => $exam->classroom?->name,
            'questions_count' => $exam->questions_count,
            'sessions_count' => $exam->sessions_count,
            'is_draft' => $exam->status === ExamStatus::Draft,
            'is_published' => $exam->status === ExamStatus::Published,
        ];
    }
}
