<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Queries;

use App\Modules\Assessment\Models\ExamSession;
use App\Modules\Assessment\Services\ExamCacheService;

/**
 * Assembles the runner payload for the student exam view.
 *
 * ╔══════════════════════════════════════════════════════════════╗
 * ║  ALL repeated data (exam config, questions) is read from     ║
 * ║  Redis via ExamCacheService (Cache-Aside pattern).           ║
 * ║  Direct DB queries are forbidden on this hot path.           ║
 * ╚══════════════════════════════════════════════════════════════╝
 */
final class ExamRunnerQuery
{
    public function __construct(
        private readonly ExamCacheService $cache
    ) {}

    /** @return array<string, mixed> */
    public function __invoke(ExamSession $session): array
    {
        // ── 1. Exam config from Redis ─────────────────────────────────
        $examData  = $this->cache->rememberExam($session->exam_id);

        // ── 2. Questions from Redis (no correct_answer in payload) ────
        $questions = $this->cache->rememberQuestions($session->exam_id);

        // ── 3. Deterministic shuffle using session seed ───────────────
        if ($examData['shuffle_questions']) {
            $seed = crc32($session->shuffle_seed);
            usort($questions, static function () use (&$seed): int {
                $seed = ($seed * 1103515245 + 12345) & 0x7fffffff;
                return ($seed % 3) - 1;
            });
            $questions = array_values($questions);
        }

        // ── 4. Student's saved answers from Redis ─────────────────────
        $answers = $this->cache->rememberSessionAnswers($session);

        // ── 5. Build final payload ────────────────────────────────────
        return [
            'session' => [
                'id'              => $session->id,
                'student_name'    => $session->student?->official_name
                    ?? $session->student?->username
                    ?? 'الطالب',
                'status'          => $session->status->value,
                'expires_at'      => $session->expires_at?->toIso8601String(),
                'server_time'     => now()->toIso8601String(),
                'offline_seconds' => $session->offline_seconds,
                'integrity_index' => $session->integrity_index,
                'face_descriptor' => json_decode(
                    $session->student?->face_descriptor ?? '[]',
                    true
                ),
            ],
            'exam' => [
                'title'            => $examData['title'],
                'security_level'   => $examData['security_level'],
                'monitors'         => $examData['monitors'],
                'duration_minutes' => $examData['duration_minutes'],
            ],
            'config' => [
                'autosave_interval_seconds'  => $examData['autosave_interval'],
                'heartbeat_interval_seconds' => $examData['heartbeat_interval'],
            ],
            'questions' => array_map(
                fn (array $q, int $i): array => $this->presentQuestion(
                    $q,
                    $i,
                    $answers[$q['id']] ?? null
                ),
                $questions,
                array_keys($questions)
            ),
        ];
    }

    /**
     * @param  array<string, mixed>      $question  Cached question row
     * @param  array<string, mixed>|null $saved     Cached answer or null
     * @return array<string, mixed>
     */
    private function presentQuestion(array $question, int $index, ?array $saved): array
    {
        return [
            'id'                 => $question['id'],
            'order'              => $index + 1,
            'type'               => $question['type'],
            'type_label'         => $question['type_label'],
            'has_options'        => $question['has_options'],
            'prompt'             => $question['prompt'],
            'options'            => $question['options'],
            'points'             => $question['points'],
            'answer'             => $saved['answer'] ?? null,
            'time_spent_seconds' => $saved['time_spent_seconds'] ?? 0,
        ];
    }
}
