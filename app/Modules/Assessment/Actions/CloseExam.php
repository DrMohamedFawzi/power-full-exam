<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Actions;

use App\Modules\Assessment\Enums\ExamStatus;
use App\Modules\Assessment\Models\Exam;

final class CloseExam
{
    public function __invoke(Exam $exam): Exam
    {
        $exam->update(['status' => ExamStatus::Closed->value]);

        return $exam;
    }
}
