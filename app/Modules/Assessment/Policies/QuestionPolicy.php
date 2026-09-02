<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Policies;

use App\Modules\Assessment\Models\Question;
use App\Modules\Identity\Models\User;

/**
 * Question mutability is delegated to ExamPolicy::manageQuestions — a question
 * is only ever as editable as its parent exam.
 */
final class QuestionPolicy
{
    public function __construct(private readonly ExamPolicy $examPolicy) {}

    public function update(User $user, Question $question): bool
    {
        return $this->examPolicy->manageQuestions($user, $question->exam);
    }

    public function delete(User $user, Question $question): bool
    {
        return $this->examPolicy->manageQuestions($user, $question->exam);
    }
}
