<?php

declare(strict_types=1);

namespace App\Modules\Academics\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Role-gated by the `role:teacher` route middleware; no per-object ownership
 * to check on creation.
 */
final class StoreClassroomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
