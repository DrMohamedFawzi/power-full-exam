<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Http\Requests;

use App\Modules\Assessment\Enums\QuestionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Persists the questions a teacher kept after reviewing the AI-generated draft. */
class StoreAiQuestionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageQuestions', $this->route('exam')) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.type' => ['required', Rule::in(array_column(QuestionType::cases(), 'value'))],
            'questions.*.prompt' => ['required', 'string', 'max:5000'],
            'questions.*.options' => ['nullable', 'array'],
            'questions.*.options.*' => ['string', 'max:1000'],
            'questions.*.correct_answer' => ['required', 'array', 'min:1'],
            'questions.*.explanation' => ['nullable', 'string', 'max:2000'],
            'questions.*.points' => ['required', 'numeric', 'min:0.1', 'max:100'],
        ];
    }
}
