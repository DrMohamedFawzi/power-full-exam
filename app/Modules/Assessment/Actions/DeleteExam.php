<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Actions;

use App\Modules\Assessment\Models\Exam;

final class DeleteExam
{
    public function __invoke(Exam $exam): void
    {
        $exam->delete();
    }
}
