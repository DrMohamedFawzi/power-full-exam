<?php

declare(strict_types=1);

namespace App\Modules\Academics\Queries;

use App\Modules\Academics\Enums\EnrollmentStatus;
use App\Modules\Academics\Models\Classroom;
use App\Modules\Identity\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

final class TeacherClassroomsQuery
{
    public function __invoke(User $teacher): LengthAwarePaginator
    {
        return Classroom::query()
            ->where('teacher_id', $teacher->id)
            ->withCount([
                'students as students_count',
                'enrollmentRequests as pending_count' => fn ($query) => $query->where(
                    'status',
                    EnrollmentStatus::Pending->value,
                ),
            ])
            ->orderBy('is_archived')
            ->latest()
            ->paginate(20)
            ->withQueryString();
    }
}
