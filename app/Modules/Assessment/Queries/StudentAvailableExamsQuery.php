<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Queries;

use App\Modules\Academics\Enums\EnrollmentStatus;
use App\Modules\Academics\Models\EnrollmentRequest;
use App\Modules\Assessment\Enums\ExamMode;
use App\Modules\Assessment\Models\Exam;
use App\Modules\Assessment\Models\ExamSession;
use App\Modules\Identity\Models\User;

/**
 * Exams a student may currently sit: published/open exams in their approved
 * classrooms, plus their own self-study mock exams. Shaped for the view —
 * no Eloquent model ever reaches Blade.
 */
final class StudentAvailableExamsQuery
{
    /** @return array<int, array<string, mixed>> */
    public function __invoke(User $student): array
    {
        $classroomIds = EnrollmentRequest::query()
            ->where('student_id', $student->id)
            ->where('status', EnrollmentStatus::Approved->value)
            ->pluck('classroom_id');

        $exams = Exam::query()
            ->openNow()
            ->where(function ($query) use ($classroomIds, $student): void {
                $query->whereIn('classroom_id', $classroomIds)
                    ->orWhere(function ($mock) use ($student): void {
                        $mock->where('mode', ExamMode::MockStudent->value)
                            ->where('created_by', $student->id);
                    });
            })
            ->with('classroom')
            ->latest()
            ->get();

        return $exams->map(fn (Exam $exam): array => $this->present($exam, $student))->all();
    }

    /** @return array<string, mixed> */
    private function present(Exam $exam, User $student): array
    {
        $attemptsUsed = ExamSession::query()
            ->where('exam_id', $exam->id)
            ->where('student_id', $student->id)
            ->where('status', '!=', 'active')
            ->count();

        $activeSessionId = ExamSession::query()
            ->where('exam_id', $exam->id)
            ->where('student_id', $student->id)
            ->active()
            ->value('id');

        return [
            'id' => $exam->id,
            'code' => $exam->code,
            'title' => $exam->title,
            'description' => $exam->description,
            'duration_minutes' => $exam->duration_minutes,
            'security_level' => [
                'value' => $exam->security_level->value,
                'label' => $exam->security_level->label(),
                'color' => $exam->security_level->color(),
            ],
            'mode' => [
                'value' => $exam->mode->value,
                'label' => $exam->mode->label(),
                'color' => $exam->mode->color(),
            ],
            'classroom_name' => $exam->classroom?->name,
            'attempts_used' => $attemptsUsed,
            'max_attempts' => $exam->max_attempts,
            'active_session_id' => $activeSessionId,
            'can_start' => $activeSessionId !== null || $attemptsUsed < $exam->max_attempts,
        ];
    }
}
