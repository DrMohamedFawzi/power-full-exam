<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Http\Requests;

use App\Modules\Assessment\Models\ExamSession;
use Illuminate\Foundation\Http\FormRequest;

class SaveAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('answer', $this->route('session')) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'question_id' => ['required', 'integer', 'exists:questions,id'],
            'answer' => ['present', 'array'],
            'time_spent_seconds' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function session(): ExamSession
    {
        return $this->route('session');
    }
}
