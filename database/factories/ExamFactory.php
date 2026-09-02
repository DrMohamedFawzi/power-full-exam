<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Assessment\Enums\ExamMode;
use App\Modules\Assessment\Enums\ExamStatus;
use App\Modules\Assessment\Enums\SecurityLevel;
use App\Modules\Assessment\Models\Exam;
use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Exam>
 */
class ExamFactory extends Factory
{
    protected $model = Exam::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('EXM-????????'),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'classroom_id' => null,
            'created_by' => User::factory()->teacher(),
            'duration_minutes' => 60,
            'security_level' => SecurityLevel::Strict->value,
            'mode' => ExamMode::Official->value,
            'status' => ExamStatus::Published->value,
            'shuffle_questions' => true,
            'preserve_time_offline' => false,
            'max_attempts' => 1,
            'opens_at' => null,
            'closes_at' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => ['status' => ExamStatus::Draft->value]);
    }

    public function closed(): static
    {
        return $this->state(fn (): array => ['status' => ExamStatus::Closed->value]);
    }

    public function security(SecurityLevel $level): static
    {
        return $this->state(fn (): array => ['security_level' => $level->value]);
    }

    public function mode(ExamMode $mode): static
    {
        return $this->state(fn (): array => ['mode' => $mode->value]);
    }

    public function maxAttempts(int $attempts): static
    {
        return $this->state(fn (): array => ['max_attempts' => $attempts]);
    }
}
