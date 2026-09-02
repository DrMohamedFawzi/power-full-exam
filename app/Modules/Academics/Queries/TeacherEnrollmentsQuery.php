<?php

declare(strict_types=1);

namespace App\Modules\Academics\Queries;

use App\Modules\Academics\Enums\EnrollmentStatus;
use App\Modules\Academics\Models\EnrollmentRequest;
use App\Modules\Identity\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

final class TeacherEnrollmentsQuery
{
    public function __invoke(User $teacher, ?int $classroomId = null, ?EnrollmentStatus $status = null): LengthAwarePaginator
    {
        return EnrollmentRequest::query()
            ->whereHas('classroom', fn ($query) => $query->where('teacher_id', $teacher->id))
            ->when($classroomId, fn ($query) => $query->where('classroom_id', $classroomId))
            ->when($status, fn ($query) => $query->where('status', $status->value))
            ->with(['student:id,official_name,username,email', 'classroom:id,name,code'])
            ->orderByRaw("status = 'pending' desc")
            ->latest()
            ->paginate(20)
            ->withQueryString();
    }
}
