<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Actions;

use App\Modules\Assessment\Models\Question;

final class DuplicateQuestion
{
    public function __invoke(Question $question): Question
    {
        $nextPosition = 1 + (int) $question->exam->questions()->max('position');

        return $question->exam->questions()->create([
            'type' => $question->type->value,
            'prompt' => $question->prompt,
            'options' => $question->options,
            'correct_answer' => $question->correct_answer,
            'explanation' => $question->explanation,
            'points' => $question->points,
            'position' => $nextPosition,
        ]);
    }
}
