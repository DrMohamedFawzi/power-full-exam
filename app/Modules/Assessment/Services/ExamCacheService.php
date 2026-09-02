<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Services;

use App\Modules\Assessment\Models\Exam;
use App\Modules\Assessment\Models\ExamSession;
use Illuminate\Support\Facades\Cache;

/**
 * Cache-Aside service for exam runtime data.
 *
 * ALL reads that are repeated across many students (exam config, questions)
 * must go through this service — never directly to the DB.
 *
 * Cache keys:
 *   exam:{id}:config   → Exam attributes + security settings (TTL 10 min)
 *   exam:{id}:questions → Ordered question list without correct answers (TTL 10 min)
 *   session:{id}:answers → Student's answers for resume (TTL 2 hours)
 *
 * Invalidation is called from mutation actions (PublishExam, UpdateQuestion, etc.)
 */
final class ExamCacheService
{
    private const EXAM_CONFIG_TTL   = 600;   // 10 minutes
    private const QUESTIONS_TTL     = 600;   // 10 minutes
    private const SESSION_ANSWER_TTL = 7200; // 2 hours

    // ──────────────────────────────────────────────────────────────
    // Reads
    // ──────────────────────────────────────────────────────────────

    /**
     * Returns exam attributes from Redis; falls back to DB and re-populates cache.
     *
     * @return array<string, mixed>
     */
    public function rememberExam(int $examId): array
    {
        return Cache::remember(
            key: "exam:{$examId}:config",
            ttl: self::EXAM_CONFIG_TTL,
            callback: function () use ($examId): array {
                /** @var Exam $exam */
                $exam = Exam::query()->findOrFail($examId);

                return [
                    'id'                  => $exam->id,
                    'title'               => $exam->title,
                    'duration_minutes'    => $exam->duration_minutes,
                    'security_level'      => $exam->security_level->value,
                    'monitors'            => $exam->security_level->monitors(),
                    'shuffle_questions'   => $exam->shuffle_questions,
                    'mode'                => $exam->mode->value,
                    'status'              => $exam->status->value,
                    'opens_at'            => $exam->opens_at?->toIso8601String(),
                    'closes_at'           => $exam->closes_at?->toIso8601String(),
                    'autosave_interval'   => (int) config('aegis.exam.autosave_interval_seconds'),
                    'heartbeat_interval'  => (int) config('aegis.exam.heartbeat_interval_seconds'),
                ];
            }
        );
    }

    /**
     * Returns ordered questions (without correct_answer) from Redis.
     *
     * @return array<int, array<string, mixed>>
     */
    public function rememberQuestions(int $examId): array
    {
        return Cache::remember(
            key: "exam:{$examId}:questions",
            ttl: self::QUESTIONS_TTL,
            callback: function () use ($examId): array {
                $exam = Exam::query()->with('questions')->findOrFail($examId);

                return $exam->questions
                    ->map(fn ($q): array => [
                        'id'         => $q->id,
                        'position'   => $q->position,
                        'type'       => $q->type->value,
                        'type_label' => $q->type->label(),
                        'has_options'=> $q->type->hasOptions(),
                        'prompt'     => $q->prompt,
                        'options'    => $q->options,
                        'points'     => (float) $q->points,
                    ])
                    ->values()
                    ->all();
            }
        );
    }

    /**
     * Cache per-session answers for fast resume (avoids answers table scan).
     *
     * @return array<int, array<string, mixed>>
     */
    public function rememberSessionAnswers(ExamSession $session): array
    {
        return Cache::remember(
            key: "session:{$session->id}:answers",
            ttl: self::SESSION_ANSWER_TTL,
            callback: fn (): array => $session->answers()
                ->get()
                ->keyBy('question_id')
                ->map(fn ($a): array => [
                    'answer'            => $a->answer,
                    'time_spent_seconds'=> $a->time_spent_seconds,
                ])
                ->all()
        );
    }

    // ──────────────────────────────────────────────────────────────
    // Cache invalidation (call after any mutation)
    // ──────────────────────────────────────────────────────────────

    public function invalidateExam(int $examId): void
    {
        Cache::forget("exam:{$examId}:config");
        Cache::forget("exam:{$examId}:questions");
    }

    public function invalidateSessionAnswers(int $sessionId): void
    {
        Cache::forget("session:{$sessionId}:answers");
    }

    /**
     * Refresh a single question entry within the cached questions list,
     * without busting the entire exam's question cache (surgical update).
     */
    public function invalidateExamQuestions(int $examId): void
    {
        Cache::forget("exam:{$examId}:questions");
    }

    /**
     * Append a freshly-saved answer into the session answers cache directly —
     * avoids a round-trip to DB just to refresh Redis after autosave.
     *
     * @param array<int, mixed> $answer
     */
    public function pushAnswerToCache(int $sessionId, int $questionId, array $answer, int $timeSpentSeconds): void
    {
        $cacheKey = "session:{$sessionId}:answers";
        $existing = Cache::get($cacheKey, []);

        $existing[$questionId] = [
            'answer'             => $answer,
            'time_spent_seconds' => $timeSpentSeconds,
        ];

        Cache::put($cacheKey, $existing, self::SESSION_ANSWER_TTL);
    }
}
