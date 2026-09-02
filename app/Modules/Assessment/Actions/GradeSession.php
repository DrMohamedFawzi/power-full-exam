<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Actions;

use App\Modules\Assessment\Models\ExamAnswer;
use App\Modules\Assessment\Models\ExamSession;
use Illuminate\Support\Facades\DB;

/**
 * Server-side, auto-gradable-only scoring. Short answers are left for the
 * teacher and never contribute to the score here. Idempotent: safe to re-run
 * whenever the answer set changes (e.g. after a teacher grades a short
 * answer elsewhere), since it only ever recomputes from the stored answers.
 */
final class GradeSession
{
    public function __invoke(ExamSession $session): ExamSession
    {
        $answers = $session->answers()->with('question')->get();

        DB::transaction(function () use ($answers): void {
            foreach ($answers as $answer) {
                $this->gradeAnswer($answer);
            }
        });

        $session->score = $answers->sum('points_awarded');
        $session->save();

        return $session;
    }

    private function gradeAnswer(ExamAnswer $answer): void
    {
        $question = $answer->question;

        if ($question === null || ! $question->type->isAutoGradable()) {
            return;
        }

        $isCorrect = $this->answersMatch((array) $answer->answer, (array) $question->correct_answer);

        $answer->is_correct = $isCorrect;
        $answer->points_awarded = $isCorrect ? (float) $question->points : 0.0;
        $answer->save();
    }

    /**
     * @param  array<int, mixed>  $given
     * @param  array<int, mixed>  $expected
     */
    private function answersMatch(array $given, array $expected): bool
    {
        $normalize = static fn (array $values): array => collect($values)
            ->map(static fn (mixed $v): string => mb_strtolower(trim((string) $v)))
            ->sort()
            ->values()
            ->all();

        return $normalize($given) === $normalize($expected);
    }
}
