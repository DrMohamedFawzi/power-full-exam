<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Actions;

use App\Modules\Assessment\Models\Exam;
use Illuminate\Support\Facades\DB;

final class ReorderQuestions
{
    /** @param list<int> $orderedQuestionIds */
    public function __invoke(Exam $exam, array $orderedQuestionIds): void
    {
        DB::transaction(function () use ($exam, $orderedQuestionIds): void {
            foreach ($orderedQuestionIds as $index => $questionId) {
                $exam->questions()
                    ->where('id', $questionId)
                    ->update(['position' => $index + 1]);
            }
        });
    }
}
