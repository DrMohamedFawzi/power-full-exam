<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Assessment\Models\ExamSession;
use App\Modules\Identity\Models\User;
use App\Modules\Proctoring\Enums\ViolationType;
use App\Modules\Proctoring\Models\Violation;
use App\Support\Enums\Severity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Violation>
 */
class ViolationFactory extends Factory
{
    protected $model = Violation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = ViolationType::TabSwitch;

        return [
            'exam_session_id' => ExamSession::factory(),
            'student_id' => User::factory()->student(),
            'type' => $type->value,
            'severity' => $type->severity()->value,
            'penalty' => $type->severity()->penalty(),
            'details' => null,
            'metadata' => null,
            'detected_at' => now(),
        ];
    }

    public function type(ViolationType $type): static
    {
        return $this->state(fn (): array => [
            'type' => $type->value,
            'severity' => $type->severity()->value,
            'penalty' => $type->severity()->penalty(),
        ]);
    }

    public function severity(Severity $severity): static
    {
        return $this->state(fn (): array => [
            'severity' => $severity->value,
            'penalty' => $severity->penalty(),
        ]);
    }

    public function forSession(ExamSession $session): static
    {
        return $this->state(fn (): array => [
            'exam_session_id' => $session->id,
            'student_id' => $session->student_id,
        ]);
    }
}
