<?php

declare(strict_types=1);

namespace App\Modules\Overwatch\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreBannedIpRequest extends FormRequest
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
            'ip_address' => ['required', 'ip'],
            'reason' => ['nullable', 'string', 'max:255'],
            'expires_in_minutes' => ['nullable', 'integer', 'min:1', 'max:525600'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ip_address.required' => 'عنوان IP مطلوب.',
            'ip_address.ip' => 'صيغة عنوان IP غير صحيحة.',
            'reason.max' => 'السبب طويل جدًا.',
            'expires_in_minutes.integer' => 'مدة الحظر يجب أن تكون رقمًا صحيحًا.',
        ];
    }
}
