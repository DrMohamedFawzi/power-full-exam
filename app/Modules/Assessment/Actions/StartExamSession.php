<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Actions;

use App\Modules\Assessment\Enums\ExamStatus;
use App\Modules\Assessment\Enums\SessionStatus;
use App\Modules\Assessment\Models\Exam;
use App\Modules\Assessment\Models\ExamSession;
use App\Modules\Identity\Actions\BindDevice;
use App\Modules\Identity\Data\FingerprintPayload;
use App\Modules\Identity\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Str;

/**
 * Creates a session, or resumes an already-active one instead of starting a
 * second attempt. Device binding delegates to Identity's BindDevice so
 * trust/fingerprint logic lives in one place.
 */
final class StartExamSession
{
    public function __invoke(User $student, Exam $exam, ?string $deviceHash, FingerprintPayload $fingerprint, string $ip): ExamSession
    {
        $existing = ExamSession::query()
            ->where('exam_id', $exam->id)
            ->where('student_id', $student->id)
            ->active()
            ->first();

        if ($existing !== null) {
            return app(ExpireExamSession::class)->ifDue($existing);
        }

        abort_unless($this->isOpen($exam), 403, 'هذا الاختبار غير متاح حالياً.');

        $priorAttempts = ExamSession::query()
            ->where('exam_id', $exam->id)
            ->where('student_id', $student->id)
            ->where('status', '!=', SessionStatus::Active->value)
            ->count();

        if ($priorAttempts >= $exam->max_attempts) {
            throw new AuthorizationException('لقد استنفدت عدد المحاولات المسموح بها لهذا الاختبار.');
        }

        return ExamSession::query()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'device_id' => $deviceHash !== null
                ? app(BindDevice::class)($student, $deviceHash, $fingerprint, ip: $ip)->device->id
                : null,
            'status' => SessionStatus::Active->value,
            'shuffle_seed' => Str::random(16),
            'integrity_index' => 100,
            'offline_seconds' => 0,
            'started_at' => now(),
            'expires_at' => now()->addMinutes($exam->duration_minutes),
        ]);
    }

    private function isOpen(Exam $exam): bool
    {
        return $exam->status === ExamStatus::Published
            && ($exam->opens_at === null || $exam->opens_at->isPast())
            && ($exam->closes_at === null || $exam->closes_at->isFuture());
    }
}
