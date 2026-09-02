<?php

declare(strict_types=1);

namespace App\Modules\Proctoring\Http\Requests;

use App\Modules\Assessment\Models\ExamSession;
use App\Modules\Proctoring\Enums\ViolationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * The signal set is closed: `type` must be one of ViolationType's cases, so a
 * tampered client cannot invent violation types or flood the table.
 */
class StoreSignalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('answer', $this->route('session')) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'signals' => ['required', 'array', 'min:1', 'max:50'],
            'signals.*.type' => ['required', new Enum(ViolationType::class)],
            'signals.*.details' => ['nullable', 'string', 'max:500'],
            'signals.*.metadata' => ['nullable', 'array'],
        ];
    }

    public function session(): ExamSession
    {
        return $this->route('session');
    }

    /** @return array<int, array{type: ViolationType, details: ?string, metadata: array<string, mixed>}> */
    public function signals(): array
    {
        return array_map(
            static fn (array $signal): array => [
                'type' => ViolationType::from($signal['type']),
                'details' => $signal['details'] ?? null,
                'metadata' => (array) ($signal['metadata'] ?? []),
            ],
            (array) $this->input('signals', []),
        );
    }
}
