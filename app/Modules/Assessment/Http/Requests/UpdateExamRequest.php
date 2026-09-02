<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Http\Requests;

use App\Modules\Assessment\Enums\ExamMode;
use App\Modules\Assessment\Enums\SecurityLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('exam')) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'classroom_id' => [
                'nullable',
                'integer',
                Rule::exists('classrooms', 'id')->where('teacher_id', $this->user()?->id),
            ],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:600'],
            'security_level' => ['required', Rule::in(array_column(SecurityLevel::cases(), 'value'))],
            'mode' => ['required', Rule::in(array_column(ExamMode::cases(), 'value'))],
            'shuffle_questions' => ['boolean'],
            'preserve_time_offline' => ['boolean'],
            'max_attempts' => ['required', 'integer', 'min:1', 'max:10'],
            'opens_at' => ['nullable', 'date'],
            'closes_at' => ['nullable', 'date', 'after:opens_at'],
        ];
    }

    /** @return array<string, mixed> */
    public function validatedData(): array
    {
        $data = $this->validated();
        $data['shuffle_questions'] = $this->boolean('shuffle_questions');
        $data['preserve_time_offline'] = $this->boolean('preserve_time_offline');

        return $data;
    }
}
