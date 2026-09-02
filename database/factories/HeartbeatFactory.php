<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Assessment\Models\ExamSession;
use App\Modules\Proctoring\Models\Heartbeat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Heartbeat>
 */
class HeartbeatFactory extends Factory
{
    protected $model = Heartbeat::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exam_session_id' => ExamSession::factory(),
            'status' => 'online',
            'duration_seconds' => 0,
            'detected_at' => now(),
        ];
    }

    public function offline(int $seconds): static
    {
        return $this->state(fn (): array => [
            'status' => 'offline',
            'duration_seconds' => $seconds,
        ]);
    }
}
