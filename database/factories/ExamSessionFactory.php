<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Assessment\Enums\SessionStatus;
use App\Modules\Assessment\Models\Exam;
use App\Modules\Assessment\Models\ExamSession;
use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ExamSession>
 */
class ExamSessionFactory extends Factory
{
    protected $model = ExamSession::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exam_id' => Exam::factory(),
            'student_id' => User::factory()->student(),
            'device_id' => null,
            'status' => SessionStatus::Active->value,
            'shuffle_seed' => Str::random(16),
            'score' => null,
            'integrity_index' => 100,
            'offline_seconds' => 0,
            'started_at' => now(),
            'expires_at' => now()->addHour(),
            'submitted_at' => null,
        ];
    }

    public function status(SessionStatus $status): static
    {
        return $this->state(fn (): array => ['status' => $status->value]);
    }

    public function expired(): static
    {
        return $this->state(fn (): array => ['expires_at' => now()->subMinutes(5)]);
    }

    public function integrity(int $index): static
    {
        return $this->state(fn (): array => ['integrity_index' => $index]);
    }
}
