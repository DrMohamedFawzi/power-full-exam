<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Jobs;

use App\Modules\Assessment\Models\ExamAnswer;
use App\Modules\Assessment\Services\ExamCacheService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Persists a single student answer to the DB asynchronously.
 *
 * The SaveAnswer action no longer blocks the request lifecycle.
 * Instead it:
 *   1. Immediately updates the Redis answer cache (so the student sees
 *      their answer on refresh without waiting for the DB write).
 *   2. Dispatches this job to the `exam-answers` queue.
 *   3. Returns instantly → request completes in < 5ms.
 *
 * This job then performs the actual DB upsert off the request cycle.
 */
final class PersistAnswerJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;
    public int $backoff = 2;

    /** @param array<int, mixed> $answer */
    public function __construct(
        private readonly int   $sessionId,
        private readonly int   $questionId,
        private readonly array $answer,
        private readonly int   $timeSpentSeconds,
    ) {
        $this->onQueue('exam-answers');
    }

    public function handle(): void
    {
        ExamAnswer::query()->updateOrCreate(
            [
                'exam_session_id' => $this->sessionId,
                'question_id'     => $this->questionId,
            ],
            [
                'answer'             => $this->answer,
                'time_spent_seconds' => $this->timeSpentSeconds,
            ]
        );

        // Invalidate the session answer cache so next Redis read reflects DB truth.
        app(ExamCacheService::class)->invalidateSessionAnswers($this->sessionId);
    }
}
