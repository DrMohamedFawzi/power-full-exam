<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Http\Requests;

use App\Modules\Assessment\Enums\QuestionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateAiQuestionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageQuestions', $this->route('exam')) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'topic' => ['required', 'string', 'max:255'],
            'count' => ['required', 'integer', 'min:1', 'max:'.config('aegis.ai.max_questions')],
            'difficulty' => ['required', 'string', Rule::in(['easy', 'medium', 'hard'])],
            'language' => ['required', 'string', Rule::in(['ar', 'en'])],
            'types' => ['required', 'array', 'min:1'],
            'types.*' => [Rule::in(array_column(QuestionType::cases(), 'value'))],
        ];
    }
}
