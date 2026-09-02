<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Queries;

use App\Modules\Academics\Models\Classroom;
use App\Modules\Identity\Models\User;

/** Options for the "classroom" select on the exam form — the teacher's own, active classrooms. */
final class TeacherClassroomOptionsQuery
{
    /** @return array<int, string> */
    public function __invoke(User $teacher): array
    {
        return Classroom::query()
            ->where('teacher_id', $teacher->id)
            ->active()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
