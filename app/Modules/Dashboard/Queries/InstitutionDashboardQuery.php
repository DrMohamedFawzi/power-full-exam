<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Queries;

use App\Modules\Academics\Models\Classroom;
use App\Modules\Assessment\Enums\SessionStatus;
use App\Modules\Assessment\Models\Exam;
use App\Modules\Assessment\Models\ExamSession;
use App\Modules\Identity\Models\User;
use App\Modules\Overwatch\Models\BannedIp;
use App\Modules\Overwatch\Models\Threat;
use App\Support\Enums\Role;
use Illuminate\Support\Collection;

final class InstitutionDashboardQuery
{
    /**
     * @return array{
     *     stats: array<string, int>,
     *     pending_teachers: Collection<int, User>,
     *     busiest_classrooms: Collection<int, Classroom>,
     *     recent_threats: Collection<int, Threat>,
     *     flagged_sessions: Collection<int, ExamSession>
     * }
     */
    public function __invoke(User $admin): array
    {
        $institutionId = $admin->institution_id;

        $memberIds = User::query()
            ->where('institution_id', $institutionId)
            ->pluck('id');

        $classroomIds = Classroom::query()
            ->whereIn('teacher_id', $memberIds)
            ->pluck('id');

        $examIds = Exam::query()
            ->whereIn('classroom_id', $classroomIds)
            ->pluck('id');

        $flagThreshold = (int) config('aegis.proctoring.flag_threshold');

        return [
            'stats' => [
                'teachers' => User::query()
                    ->where('institution_id', $institutionId)
                    ->where('role', Role::Teacher->value)
                    ->count(),
                'pending_teachers' => User::query()
                    ->where('institution_id', $institutionId)
                    ->where('role', Role::Teacher->value)
                    ->where('is_approved', false)
                    ->count(),
                'students' => User::query()
                    ->where('institution_id', $institutionId)
                    ->where('role', Role::Student->value)
                    ->count(),
                'classrooms' => $classroomIds->count(),
                'exams' => $examIds->count(),
                'threats_today' => Threat::query()
                    ->whereDate('detected_at', today())
                    ->count(),
                'active_bans' => BannedIp::query()->active()->count(),
            ],
            'pending_teachers' => User::query()
                ->select(['id', 'official_name', 'email', 'created_at'])
                ->where('institution_id', $institutionId)
                ->where('role', Role::Teacher->value)
                ->where('is_approved', false)
                ->latest()
                ->limit(5)
                ->get(),
            'busiest_classrooms' => Classroom::query()
                ->select(['id', 'name', 'code', 'teacher_id'])
                ->with('teacher:id,official_name')
                ->whereIn('id', $classroomIds)
                ->withCount('students as students_count')
                ->orderByDesc('students_count')
                ->limit(5)
                ->get(),
            'recent_threats' => Threat::query()
                ->latest('detected_at')
                ->limit(6)
                ->get(),
            'flagged_sessions' => ExamSession::query()
                ->with(['exam:id,title', 'student:id,official_name'])
                ->whereIn('exam_id', $examIds)
                ->where('status', SessionStatus::Submitted->value)
                ->where('integrity_index', '<', $flagThreshold)
                ->latest('submitted_at')
                ->limit(6)
                ->get(),
        ];
    }
}
