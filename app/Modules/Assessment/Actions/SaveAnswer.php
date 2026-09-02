<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Actions;

use App\Modules\Assessment\Jobs\PersistAnswerJob;
use App\Modules\Assessment\Models\ExamSession;
use App\Modules\Assessment\Services\ExamCacheService;

/**
 * Autosave for one question — now fully async.
 *
 * The request cycle does only two things:
 *   1. Push the answer into the Redis session cache (instant read on resume).
 *   2. Dispatch PersistAnswerJob → queue `exam-answers` → Horizon worker.
 *
 * The DB upsert happens off-cycle. No DB hit during the student's exam.
 */
final class SaveAnswer
{
    public function __construct(
        private readonly ExamCacheService $cache
    ) {}

    /** @param array<int, mixed> $answer */
    public function __invoke(ExamSession $session, int $questionId, array $answer, int $timeSpentSeconds): void
    {
        // ── 1. Update Redis immediately (student sees saved answer on refresh) ──
        $this->cache->pushAnswerToCache(
            sessionId:        $session->id,
            questionId:       $questionId,
            answer:           $answer,
            timeSpentSeconds: $timeSpentSeconds,
        );

        // ── 2. Dispatch async DB write ─────────────────────────────────────────
        PersistAnswerJob::dispatch(
            sessionId:        $session->id,
            questionId:       $questionId,
            answer:           $answer,
            timeSpentSeconds: $timeSpentSeconds,
        );
    }
}
