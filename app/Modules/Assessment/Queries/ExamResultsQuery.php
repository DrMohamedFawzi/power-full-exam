<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Queries;

use App\Modules\Assessment\Models\Exam;
use App\Modules\Assessment\Models\ExamAnswer;
use App\Modules\Assessment\Models\ExamSession;

/** Submissions + per-question difficulty stats for a single exam's results page. */
final class ExamResultsQuery
{
    /** @return array<string, mixed> */
    public function __invoke(Exam $exam): array
    {
        $sessions = $exam->sessions()
            ->with(['student:id,official_name,username', 'violations'])
            ->latest('submitted_at')
            ->get();

        return [
            'exam' => $exam,
            'sessions' => $sessions->map(fn (ExamSession $session): array => [
                'id' => $session->id,
                'student_name' => $session->student->official_name,
                'status' => $session->status,
                'score' => $session->score !== null ? (float) $session->score : null,
                'integrity_index' => $session->integrity_index,
                'violation_count' => $session->violations->count(),
                'duration_minutes' => $this->durationMinutes($session),
                'submitted_at' => $session->submitted_at,
            ])->all(),
            'question_stats' => $this->questionStats($exam),
        ];
    }

    private function durationMinutes(ExamSession $session): ?int
    {
        if ($session->started_at === null || $session->submitted_at === null) {
            return null;
        }

        return (int) round($session->started_at->diffInSeconds($session->submitted_at) / 60);
    }

    /** @return list<array<string, mixed>> */
    private function questionStats(Exam $exam): array
    {
        return $exam->questions->map(function ($question): array {
            $answers = ExamAnswer::query()
                ->where('question_id', $question->id)
                ->whereNotNull('is_correct')
                ->get();
            $total = $answers->count();
            $correct = $answers->where('is_correct', true)->count();

            return [
                'id' => $question->id,
                'position' => $question->position,
                'prompt' => $question->prompt,
                'type_label' => $question->type->label(),
                'answered_count' => $total,
                'correct_percentage' => $total > 0 ? (int) round(($correct / $total) * 100) : null,
            ];
        })->all();
    }
}
