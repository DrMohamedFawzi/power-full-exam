<?php

declare(strict_types=1);

namespace App\Modules\Academics\Queries;

use App\Modules\Academics\Enums\EnrollmentStatus;
use App\Modules\Academics\Models\EnrollmentRequest;
use App\Modules\Identity\Models\Institution;
use App\Modules\Identity\Models\User;
use App\Support\Enums\Role;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Searchable, paginated roster of an institution's students with each one's
 * classroom count and whether they currently hold any approved enrollment.
 */
final class InstitutionStudentsQuery
{
    public function __invoke(Institution $institution, ?string $search = null): LengthAwarePaginator
    {
        $students = User::query()
            ->role(Role::Student)
            ->where('institution_id', $institution->id)
            ->when($search, fn ($query) => $query->where(fn ($inner) => $inner
                ->where('official_name', 'like', "%{$search}%")
                ->orWhere('username', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->orderBy('official_name')
            ->paginate(20)
            ->withQueryString();

        $classroomCounts = EnrollmentRequest::query()
            ->whereIn('student_id', $students->pluck('id'))
            ->where('status', EnrollmentStatus::Approved->value)
            ->selectRaw('student_id, count(*) as aggregate')
            ->groupBy('student_id')
            ->pluck('aggregate', 'student_id');

        $students->through(fn (User $student) => (object) [
            'id' => $student->id,
            'official_name' => $student->official_name,
            'username' => $student->username,
            'email' => $student->email,
            'classrooms_count' => $classroomCounts[$student->id] ?? 0,
            'is_enrolled' => ($classroomCounts[$student->id] ?? 0) > 0,
            'photo_status' => $student->photo_status,
            'avatar_path' => $student->avatar_path,
            'avatar_url' => $student->avatar_url,
        ]);

        return $students;
    }
}
