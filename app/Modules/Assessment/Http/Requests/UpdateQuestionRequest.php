<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Http\Requests;

use App\Modules\Assessment\Enums\QuestionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('question')) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(array_column(QuestionType::cases(), 'value'))],
            'prompt' => ['required', 'string', 'max:5000'],
            'options' => ['nullable', 'array'],
            'options.*' => ['string', 'max:1000'],
            'correct_answer' => ['required', 'array', 'min:1'],
            'explanation' => ['nullable', 'string', 'max:2000'],
            'points' => ['required', 'numeric', 'min:0.1', 'max:100'],
        ];
    }
}
