<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Assessment\Models\ExamSession;
use App\Modules\Proctoring\Models\KeystrokeSample;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KeystrokeSample>
 */
class KeystrokeSampleFactory extends Factory
{
    protected $model = KeystrokeSample::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exam_session_id' => ExamSession::factory(),
            'mean_interval_ms' => fake()->randomFloat(2, 100, 250),
            'std_deviation_ms' => fake()->randomFloat(2, 20, 60),
            'sample_size' => fake()->numberBetween(10, 40),
            'anomaly_score' => 0,
            'captured_at' => now(),
        ];
    }
}
