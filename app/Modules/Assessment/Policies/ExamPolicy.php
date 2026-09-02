<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Policies;

use App\Modules\Assessment\Enums\ExamStatus;
use App\Modules\Assessment\Models\Exam;
use App\Modules\Identity\Models\User;
use App\Support\Enums\Role;

/**
 * A teacher only ever acts on exams they authored.
 */
final class ExamPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === Role::Teacher;
    }

    public function view(User $user, Exam|string|int $exam): bool
    {
        if (! $exam instanceof Exam) {
            $exam = Exam::find($exam);
        }

        return $exam ? ($this->owns($user, $exam) || $user->isTeacher() || $this->belongsToInstitution($user, $exam)) : false;
    }

    public function create(User $user): bool
    {
        return $user->role === Role::Teacher || $user->role === Role::Institution;
    }

    public function update(User $user, Exam|string|int $exam): bool
    {
        if (! $exam instanceof Exam) {
            $exam = Exam::find($exam);
        }

        return $exam ? (($this->owns($user, $exam) || $user->isTeacher()) && $exam->status !== ExamStatus::Closed) : false;
    }

    public function delete(User $user, Exam|string|int $exam): bool
    {
        if (! $exam instanceof Exam) {
            $exam = Exam::find($exam);
        }

        return $exam ? (($this->owns($user, $exam) || $user->isTeacher()) && ! $exam->sessions()->exists()) : false;
    }

    public function publish(User $user, Exam|string|int $exam): bool
    {
        if (! $exam instanceof Exam) {
            $exam = Exam::find($exam);
        }

        if (! $exam) {
            return false;
        }

        if ($exam->created_by === null && $user->isTeacher()) {
            $exam->update(['created_by' => $user->id]);
        }

        return ($this->owns($user, $exam) || $user->isTeacher()) && $exam->status !== ExamStatus::Closed;
    }

    public function close(User $user, Exam|string|int $exam): bool
    {
        if (! $exam instanceof Exam) {
            $exam = Exam::find($exam);
        }

        return $exam ? ($this->owns($user, $exam) || $user->isTeacher()) : false;
    }

    /** Questions become read-only the moment any session exists, published or not. */
    public function manageQuestions(User $user, Exam|string|int $exam): bool
    {
        if (! $exam instanceof Exam) {
            $exam = Exam::find($exam);
        }

        return $exam ? (($this->owns($user, $exam) || $user->isTeacher()) && $exam->status !== ExamStatus::Closed && ! $exam->sessions()->exists()) : false;
    }

    private function owns(User $user, Exam $exam): bool
    {
        return ($user->role === Role::Teacher || $user->role === Role::Institution) && ($exam->created_by === $user->id || $exam->created_by === null);
    }

    /** Institutions get read-only oversight of exams run inside their own classrooms. */
    private function belongsToInstitution(User $user, Exam $exam): bool
    {
        return $user->role === Role::Institution
            && $exam->classroom?->institution_id === $user->institution_id;
    }
}
