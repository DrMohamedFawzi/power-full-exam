<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Requests;

use App\Support\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $isTeacher = $this->input('role') === Role::Teacher->value;
        $isInstitution = $this->input('role') === Role::Institution->value;

        return [
            'role' => ['required', Rule::enum(Role::class)],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users,username'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'official_name' => ['required', 'string', 'max:150'],
            'institution_id' => [$isTeacher ? 'required' : 'nullable', 'integer', 'exists:institutions,id'],
            'institution_name' => [$isInstitution ? 'required' : 'nullable', 'string', 'max:150'],
            'institution_code' => [$isInstitution ? 'required' : 'nullable', 'string', 'max:32', 'unique:institutions,code'],
            'institution_contact_email' => ['nullable', 'email', 'max:150'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'username' => 'اسم المستخدم',
            'email' => 'البريد الإلكتروني',
            'password' => 'كلمة المرور',
            'official_name' => 'الاسم الرسمي',
            'institution_id' => 'المؤسسة',
            'institution_name' => 'اسم المؤسسة',
            'institution_code' => 'رمز المؤسسة',
            'institution_contact_email' => 'البريد الإلكتروني للمؤسسة',
        ];
    }
}
