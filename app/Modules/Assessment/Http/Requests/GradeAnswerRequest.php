<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GradeAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $answer = $this->route('answer');

        return $answer !== null && ($this->user()?->can('view', $answer->session->exam) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'points_awarded' => ['required', 'numeric', 'min:0'],
        ];
    }
}
