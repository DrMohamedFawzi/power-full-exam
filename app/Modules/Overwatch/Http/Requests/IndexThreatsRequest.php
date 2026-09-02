<?php

declare(strict_types=1);

namespace App\Modules\Overwatch\Http\Requests;

use App\Modules\Overwatch\Enums\AttackType;
use App\Support\Enums\Severity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class IndexThreatsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'attack_type' => ['nullable', 'string', Rule::in(array_column(AttackType::cases(), 'value'))],
            'severity' => ['nullable', 'string', Rule::in(array_column(Severity::cases(), 'value'))],
            'ip_address' => ['nullable', 'string', 'max:45'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ];
    }
}
