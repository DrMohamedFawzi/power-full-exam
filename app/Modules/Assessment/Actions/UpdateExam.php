<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Actions;

use App\Modules\Assessment\Models\Exam;

final class UpdateExam
{
    /** @param array<string, mixed> $data */
    public function __invoke(Exam $exam, array $data): Exam
    {
        $exam->update($data);

        return $exam;
    }
}
