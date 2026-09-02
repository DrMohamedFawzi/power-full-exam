<?php

declare(strict_types=1);

namespace App\Modules\Academics\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateClassroomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('classroom')) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_archived' => ['boolean'],
        ];
    }
}
