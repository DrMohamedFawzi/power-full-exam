<?php

declare(strict_types=1);

namespace App\Modules\Academics\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

final class RejectEnrollmentFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('review', $this->route('enrollment')) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
