<?php

declare(strict_types=1);

namespace App\Modules\Academics\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

final class JoinClassroomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'size:6'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'code.required' => 'رمز الانضمام مطلوب.',
            'code.size' => 'رمز الانضمام يتكون من 6 أحرف.',
        ];
    }
}
