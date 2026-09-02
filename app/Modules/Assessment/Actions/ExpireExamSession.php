<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Actions;

use App\Modules\Assessment\Enums\SessionStatus;
use App\Modules\Assessment\Models\ExamSession;

/**
 * The server clock — never the client's — decides when a session is over.
 * Whatever was autosaved is graded and kept; nothing new can be answered.
 */
final class ExpireExamSession
{
    public function __invoke(ExamSession $session): ExamSession
    {
        if ($session->status !== SessionStatus::Active) {
            return $session;
        }

        app(GradeSession::class)($session);

        $session->status = SessionStatus::Expired;
        $session->submitted_at = now();
        $session->save();

        return $session;
    }

    /** Expire in place if the deadline has passed; returns the (possibly refreshed) session. */
    public function ifDue(ExamSession $session): ExamSession
    {
        if ($session->status === SessionStatus::Active
            && $session->expires_at !== null
            && $session->expires_at->isPast()
        ) {
            return $this($session);
        }

        return $session;
    }
}
