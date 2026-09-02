<?php

declare(strict_types=1);

namespace App\Modules\Academics\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

final class BulkApproveEnrollmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('classroom')) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'request_ids' => ['sometimes', 'array'],
            'request_ids.*' => ['integer'],
        ];
    }
}
