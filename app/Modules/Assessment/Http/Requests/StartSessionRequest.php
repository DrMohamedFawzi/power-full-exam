<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Http\Requests;

use App\Modules\Assessment\Models\Exam;
use App\Modules\Assessment\Models\ExamSession;
use App\Modules\Identity\Data\FingerprintPayload;
use Illuminate\Foundation\Http\FormRequest;

class StartSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', [ExamSession::class, $this->route('exam')]) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'device_hash' => ['nullable', 'string', 'max:64'],
            'fingerprint' => ['nullable', 'array'],
            'fingerprint.user_agent' => ['nullable', 'string'],
            'fingerprint.screen_resolution' => ['nullable', 'string', 'max:32'],
            'fingerprint.timezone' => ['nullable', 'string', 'max:64'],
            'fingerprint.canvas_hash' => ['nullable', 'string', 'max:64'],
            'fingerprint.webgl_vendor' => ['nullable', 'string', 'max:255'],
            'fingerprint.webgl_renderer' => ['nullable', 'string', 'max:255'],
            'fingerprint.is_headless' => ['nullable', 'boolean'],
        ];
    }

    public function exam(): Exam
    {
        $exam = $this->route('exam');

        if (! $exam instanceof Exam) {
            $exam = Exam::findOrFail($exam);
        }

        return $exam;
    }

    public function fingerprint(): FingerprintPayload
    {
        $data = (array) $this->input('fingerprint', []);

        return new FingerprintPayload(
            userAgent: $data['user_agent'] ?? $this->userAgent(),
            screenResolution: $data['screen_resolution'] ?? null,
            timezone: $data['timezone'] ?? null,
            canvasHash: $data['canvas_hash'] ?? null,
            webglVendor: $data['webgl_vendor'] ?? null,
            webglRenderer: $data['webgl_renderer'] ?? null,
            isHeadless: (bool) ($data['is_headless'] ?? false),
            raw: $data,
        );
    }
}
