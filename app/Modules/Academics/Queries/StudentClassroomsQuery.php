<?php

declare(strict_types=1);

namespace App\Modules\Academics\Queries;

use App\Modules\Academics\Enums\EnrollmentStatus;
use App\Modules\Academics\Models\EnrollmentRequest;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Collection;

/**
 * @return array{approved: Collection, other: Collection}
 */
final class StudentClassroomsQuery
{
    public function __invoke(User $student): array
    {
        $requests = EnrollmentRequest::query()
            ->where('student_id', $student->id)
            ->with('classroom:id,name,code,teacher_id,institution_id')
            ->with('classroom.teacher:id,official_name')
            ->latest()
            ->get();

        return [
            'approved' => $requests->where('status', EnrollmentStatus::Approved)->values(),
            'other' => $requests->where('status', '!==', EnrollmentStatus::Approved)->values(),
        ];
    }
}
