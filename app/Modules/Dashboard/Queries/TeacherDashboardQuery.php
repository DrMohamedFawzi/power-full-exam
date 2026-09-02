<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Queries;

use App\Modules\Academics\Enums\EnrollmentStatus;
use App\Modules\Academics\Models\Classroom;
use App\Modules\Academics\Models\EnrollmentRequest;
use App\Modules\Assessment\Enums\ExamStatus;
use App\Modules\Assessment\Enums\SessionStatus;
use App\Modules\Assessment\Models\Exam;
use App\Modules\Assessment\Models\ExamSession;
use App\Modules\Identity\Models\User;
use App\Modules\Proctoring\Models\Violation;
use Illuminate\Support\Collection;

final class TeacherDashboardQuery
{
    /**
     * @return array{
     *     stats: array<string, int>,
     *     live_sessions: Collection<int, ExamSession>,
     *     pending_enrollments: Collection<int, EnrollmentRequest>,
     *     recent_violations: Collection<int, Violation>
     * }
     */
    public function __invoke(User $teacher): array
    {
        $classroomIds = Classroom::query()
            ->where('teacher_id', $teacher->id)
            ->pluck('id');

        $examIds = Exam::query()
            ->where('created_by', $teacher->id)
            ->pluck('id');

        return [
            'stats' => [
                'classrooms' => $classroomIds->count(),
                'students' => EnrollmentRequest::query()
                    ->whereIn('classroom_id', $classroomIds)
                    ->where('status', EnrollmentStatus::Approved->value)
                    ->distinct('student_id')
                    ->count('student_id'),
                'pending_enrollments' => EnrollmentRequest::query()
                    ->whereIn('classroom_id', $classroomIds)
                    ->where('status', EnrollmentStatus::Pending->value)
                    ->count(),
                'published_exams' => Exam::query()
                    ->whereIn('id', $examIds)
                    ->where('status', ExamStatus::Published->value)
                    ->count(),
                'draft_exams' => Exam::query()
                    ->whereIn('id', $examIds)
                    ->where('status', ExamStatus::Draft->value)
                    ->count(),
                'live_now' => ExamSession::query()
                    ->whereIn('exam_id', $examIds)
                    ->where('status', SessionStatus::Active->value)
                    ->count(),
            ],
            'live_sessions' => ExamSession::query()
                ->with(['exam:id,title,code', 'student:id,official_name'])
                ->whereIn('exam_id', $examIds)
                ->where('status', SessionStatus::Active->value)
                ->orderBy('integrity_index')
                ->limit(6)
                ->get(),
            'pending_enrollments' => EnrollmentRequest::query()
                ->with(['student:id,official_name', 'classroom:id,name'])
                ->whereIn('classroom_id', $classroomIds)
                ->where('status', EnrollmentStatus::Pending->value)
                ->latest()
                ->limit(5)
                ->get(),
            'recent_violations' => Violation::query()
                ->with(['student:id,official_name', 'session:id,exam_id'])
                ->whereIn('exam_session_id', ExamSession::query()
                    ->whereIn('exam_id', $examIds)
                    ->select('id'))
                ->latest('detected_at')
                ->limit(8)
                ->get(),
        ];
    }
}
