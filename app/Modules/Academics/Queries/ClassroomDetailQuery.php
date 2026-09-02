<?php

declare(strict_types=1);

namespace App\Modules\Academics\Queries;

use App\Modules\Academics\Enums\EnrollmentStatus;
use App\Modules\Academics\Models\Classroom;
use Illuminate\Support\Collection;

/**
 * @return array{classroom: Classroom, approved: Collection, pending: Collection}
 */
final class ClassroomDetailQuery
{
    public function __invoke(Classroom $classroom): array
    {
        $requests = $classroom->enrollmentRequests()
            ->with('student:id,official_name,username,email')
            ->latest()
            ->get();

        return [
            'classroom' => $classroom->loadCount('students'),
            'approved' => $requests->where('status', EnrollmentStatus::Approved)->values(),
            'pending' => $requests->where('status', EnrollmentStatus::Pending)->values(),
        ];
    }
}
