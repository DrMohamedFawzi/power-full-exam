<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Actions;

use App\Modules\Assessment\Enums\SessionStatus;
use App\Modules\Assessment\Models\ExamSession;

/**
 * The student's own submit. Grades whatever was saved and closes the session.
 */
final class SubmitExamSession
{
    public function __invoke(ExamSession $session): ExamSession
    {
        if ($session->status !== SessionStatus::Active) {
            return $session;
        }

        app(GradeSession::class)($session);

        $session->status = SessionStatus::Submitted;
        $session->submitted_at = now();
        $session->save();

        return $session;
    }
}
