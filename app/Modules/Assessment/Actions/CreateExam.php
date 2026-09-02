<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Actions;

use App\Modules\Assessment\Enums\ExamStatus;
use App\Modules\Assessment\Models\Exam;
use App\Modules\Identity\Models\User;

final class CreateExam
{
    public function __construct(private readonly GenerateUniqueExamCode $generateCode) {}

    /** @param array<string, mixed> $data */
    public function __invoke(User $teacher, array $data): Exam
    {
        return Exam::query()->create([
            ...$data,
            'code' => ($this->generateCode)(),
            'created_by' => $teacher->id,
            'status' => ExamStatus::Draft->value,
        ]);
    }
}
