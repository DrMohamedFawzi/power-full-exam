<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Actions;

use App\Modules\Assessment\Models\Question;

final class UpdateQuestion
{
    /** @param array<string, mixed> $data */
    public function __invoke(Question $question, array $data): Question
    {
        $question->update($data);

        return $question;
    }
}
