<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Actions;

use App\Modules\Assessment\Models\Exam;
use App\Modules\Assessment\Models\Question;

final class AddQuestion
{
    /** @param array<string, mixed> $data */
    public function __invoke(Exam $exam, array $data): Question
    {
        $nextPosition = 1 + (int) $exam->questions()->max('position');

        return $exam->questions()->create([
            ...$data,
            'position' => $nextPosition,
        ]);
    }
}
