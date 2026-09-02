<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Actions;

use App\Modules\Assessment\Enums\SessionStatus;
use App\Modules\Assessment\Models\ExamSession;

/**
 * Ends a session for cause — the proctoring pipeline decided the student's
 * integrity index dropped too far under a strict exam. Whatever was answered
 * so far is graded and kept; the session simply stops accepting more.
 */
final class TerminateExamSession
{
    public function __invoke(ExamSession $session): ExamSession
    {
        if ($session->status !== SessionStatus::Active) {
            return $session;
        }

        app(GradeSession::class)($session);

        $session->status = SessionStatus::Terminated;
        $session->submitted_at = now();
        $session->save();

        return $session;
    }
}
