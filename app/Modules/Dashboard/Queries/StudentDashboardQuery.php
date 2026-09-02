<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Queries;

use App\Modules\Academics\Enums\EnrollmentStatus;
use App\Modules\Academics\Models\EnrollmentRequest;
use App\Modules\Assessment\Enums\ExamStatus;
use App\Modules\Assessment\Enums\SessionStatus;
use App\Modules\Assessment\Models\Exam;
use App\Modules\Assessment\Models\ExamSession;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Collection;

final class StudentDashboardQuery
{
    /**
     * @return array{
     *     stats: array<string, int|string>,
     *     active_session: ExamSession|null,
     *     upcoming: Collection<int, Exam>,
     *     recent: Collection<int, ExamSession>,
     *     average_integrity: int|null
     * }
     */
    public function __invoke(User $student): array
    {
        $classroomIds = EnrollmentRequest::query()
            ->where('student_id', $student->id)
            ->where('status', EnrollmentStatus::Approved->value)
            ->pluck('classroom_id');

        $submitted = ExamSession::query()
            ->where('student_id', $student->id)
            ->where('status', SessionStatus::Submitted->value);

        return [
            'stats' => [
                'classrooms' => $classroomIds->count(),
                'pending_requests' => EnrollmentRequest::query()
                    ->where('student_id', $student->id)
                    ->where('status', EnrollmentStatus::Pending->value)
                    ->count(),
                'completed' => (clone $submitted)->count(),
                'devices' => $student->devices()->active()->count(),
            ],
            'active_session' => ExamSession::query()
                ->with('exam:id,title,code,duration_minutes')
                ->where('student_id', $student->id)
                ->where('status', SessionStatus::Active->value)
                ->latest('started_at')
                ->first(),
            'upcoming' => Exam::query()
                ->select(['id', 'code', 'title', 'classroom_id', 'duration_minutes', 'closes_at', 'security_level'])
                ->with('classroom:id,name')
                ->whereIn('classroom_id', $classroomIds)
                ->where('status', ExamStatus::Published->value)
                ->whereDoesntHave(
                    'sessions',
                    fn ($query) => $query->where('student_id', $student->id)
                        ->where('status', SessionStatus::Submitted->value),
                )
                ->orderByRaw('closes_at IS NULL, closes_at')
                ->limit(5)
                ->get(),
            'recent' => (clone $submitted)
                ->with('exam:id,title,code')
                ->latest('submitted_at')
                ->limit(5)
                ->get(),
            'average_integrity' => (clone $submitted)->exists()
                ? (int) round((float) (clone $submitted)->avg('integrity_index'))
                : null,
        ];
    }
}
