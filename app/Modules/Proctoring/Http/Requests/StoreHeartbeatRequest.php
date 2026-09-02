<?php

declare(strict_types=1);

namespace App\Modules\Proctoring\Http\Requests;

use App\Modules\Assessment\Models\ExamSession;
use Illuminate\Foundation\Http\FormRequest;

class StoreHeartbeatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('answer', $this->route('session')) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['required', 'in:online,offline'],
            'duration_seconds' => ['nullable', 'integer', 'min:0', 'max:3600'],
            'client_time' => ['nullable', 'integer'],
        ];
    }

    public function session(): ExamSession
    {
        return $this->route('session');
    }
}
