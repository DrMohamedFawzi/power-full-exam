<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Actions;

use App\Modules\Assessment\Models\ExamAnswer;
use Illuminate\Support\Facades\DB;

/**
 * Manual grading of a (typically short-answer) response. Recomputes the whole
 * session score from scratch so it always stays reproducible from the answers.
 */
final class GradeAnswer
{
    public function __invoke(ExamAnswer $answer, float $pointsAwarded): ExamAnswer
    {
        $maxPoints = (float) $answer->question->points;
        $pointsAwarded = max(0.0, min($pointsAwarded, $maxPoints));

        DB::transaction(function () use ($answer, $pointsAwarded, $maxPoints): void {
            $answer->update([
                'points_awarded' => $pointsAwarded,
                'is_correct' => $pointsAwarded >= $maxPoints,
            ]);

            $session = $answer->session;
            $total = (float) $session->answers()->sum('points_awarded');
            $session->update(['score' => $total]);
        });

        return $answer->refresh();
    }
}
