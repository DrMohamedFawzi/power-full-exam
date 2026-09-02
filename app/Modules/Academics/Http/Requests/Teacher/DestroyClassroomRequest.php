<?php

declare(strict_types=1);

namespace App\Modules\Academics\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

final class DestroyClassroomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('delete', $this->route('classroom')) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }
}
