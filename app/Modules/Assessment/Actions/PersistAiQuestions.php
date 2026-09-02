<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Actions;

use App\Modules\Assessment\Models\Exam;
use Illuminate\Support\Facades\DB;

/**
 * Persists the questions a teacher approved after reviewing the AI draft.
 * Nothing from Gemini reaches the `questions` table without this step.
 */
final class PersistAiQuestions
{
    /** @param list<array<string, mixed>> $questions */
    public function __invoke(Exam $exam, array $questions): int
    {
        return DB::transaction(function () use ($exam, $questions): int {
            $nextPosition = 1 + (int) $exam->questions()->max('position');

            foreach ($questions as $offset => $question) {
                $exam->questions()->create([
                    ...$question,
                    'position' => $nextPosition + $offset,
                ]);
            }

            return count($questions);
        });
    }
}
