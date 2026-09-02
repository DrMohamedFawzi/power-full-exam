<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Assessment\Models\ExamAnswer;
use App\Modules\Assessment\Models\ExamSession;
use App\Modules\Assessment\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamAnswer>
 */
class ExamAnswerFactory extends Factory
{
    protected $model = ExamAnswer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exam_session_id' => ExamSession::factory(),
            'question_id' => Question::factory(),
            'answer' => ['أ'],
            'is_correct' => null,
            'points_awarded' => 0,
            'time_spent_seconds' => 0,
        ];
    }
}
