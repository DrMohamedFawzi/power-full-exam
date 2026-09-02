<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Assessment\Enums\QuestionType;
use App\Modules\Assessment\Models\Exam;
use App\Modules\Assessment\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    protected $model = Question::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exam_id' => Exam::factory(),
            'position' => 0,
            'type' => QuestionType::MultipleChoice->value,
            'prompt' => fake()->sentence().'؟',
            'options' => ['أ', 'ب', 'ج', 'د'],
            'correct_answer' => ['أ'],
            'explanation' => fake()->sentence(),
            'points' => 1,
        ];
    }

    public function type(QuestionType $type): static
    {
        return $this->state(fn (): array => ['type' => $type->value]);
    }

    public function trueFalse(bool $answer = true): static
    {
        return $this->state(fn (): array => [
            'type' => QuestionType::TrueFalse->value,
            'options' => ['صحيح', 'خطأ'],
            'correct_answer' => [$answer ? 'صحيح' : 'خطأ'],
        ]);
    }

    public function multipleSelect(): static
    {
        return $this->state(fn (): array => [
            'type' => QuestionType::MultipleSelect->value,
            'options' => ['أ', 'ب', 'ج', 'د'],
            'correct_answer' => ['أ', 'ج'],
        ]);
    }

    public function shortAnswer(): static
    {
        return $this->state(fn (): array => [
            'type' => QuestionType::ShortAnswer->value,
            'options' => null,
            'correct_answer' => ['نموذج الإجابة'],
        ]);
    }

    public function position(int $position): static
    {
        return $this->state(fn (): array => ['position' => $position]);
    }
}
