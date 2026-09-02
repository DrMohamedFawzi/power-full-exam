<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Policies;

use App\Modules\Academics\Enums\EnrollmentStatus;
use App\Modules\Academics\Models\EnrollmentRequest;
use App\Modules\Assessment\Enums\ExamMode;
use App\Modules\Assessment\Models\Exam;
use App\Modules\Assessment\Models\ExamSession;
use App\Modules\Identity\Models\User;

/**
 * Runtime authorization for a student's own attempts. Authoring/teacher-side
 * authorization on Exam itself belongs to ExamPolicy, owned elsewhere.
 */
final class ExamSessionPolicy
{
    /** May the student (or teacher creator) start (or resume) a session for this exam? */
    public function create(User $user, Exam|string|int $exam): bool
    {
        if (! $exam instanceof Exam) {
            $exam = Exam::find($exam);
        }

        if (! $exam) {
            return false;
        }

        if ($exam->created_by === $user->id) {
            return true;
        }

        return $this->hasAccess($user, $exam);
    }

    public function view(User $user, ExamSession $session): bool
    {
        return $user->id === $session->student_id || $user->id === $session->exam?->created_by;
    }

    public function answer(User $user, ExamSession $session): bool
    {
        return $user->id === $session->student_id;
    }

    public function submit(User $user, ExamSession $session): bool
    {
        return $user->id === $session->student_id;
    }

    private function hasAccess(User $user, Exam $exam): bool
    {
        if ($exam->mode === ExamMode::MockStudent) {
            return $exam->created_by === $user->id;
        }

        if ($exam->classroom_id === null) {
            return true;
        }

        return EnrollmentRequest::query()
            ->where('classroom_id', $exam->classroom_id)
            ->where('student_id', $user->id)
            ->where('status', EnrollmentStatus::Approved->value)
            ->exists();
    }
}
