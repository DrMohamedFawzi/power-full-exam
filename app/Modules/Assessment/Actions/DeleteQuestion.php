<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Actions;

use App\Modules\Assessment\Models\Question;

final class DeleteQuestion
{
    public function __invoke(Question $question): void
    {
        $exam = $question->exam;
        $question->delete();

        $exam->questions()->orderBy('position')->get()
            ->each(function (Question $remaining, int $index): void {
                $remaining->update(['position' => $index + 1]);
            });
    }
}
