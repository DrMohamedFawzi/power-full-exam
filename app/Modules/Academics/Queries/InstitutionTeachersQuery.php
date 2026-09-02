<?php

declare(strict_types=1);

namespace App\Modules\Academics\Queries;

use App\Modules\Academics\Models\Classroom;
use App\Modules\Identity\Models\Institution;
use App\Modules\Identity\Models\User;
use App\Support\Enums\Role;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Institution's teacher roster, pending approvals surfaced first.
 */
final class InstitutionTeachersQuery
{
    public function __invoke(Institution $institution): LengthAwarePaginator
    {
        $teachers = User::query()
            ->role(Role::Teacher)
            ->where('institution_id', $institution->id)
            ->orderBy('is_approved')
            ->orderBy('official_name')
            ->paginate(20)
            ->withQueryString();

        $classroomCounts = Classroom::query()
            ->whereIn('teacher_id', $teachers->pluck('id'))
            ->selectRaw('teacher_id, count(*) as aggregate')
            ->groupBy('teacher_id')
            ->pluck('aggregate', 'teacher_id');

        $teachers->through(fn (User $teacher) => (object) [
            'id' => $teacher->id,
            'official_name' => $teacher->official_name,
            'username' => $teacher->username,
            'email' => $teacher->email,
            'is_approved' => $teacher->is_approved,
            'classrooms_count' => $classroomCounts[$teacher->id] ?? 0,
            'created_at' => $teacher->created_at,
        ]);

        return $teachers;
    }
}
