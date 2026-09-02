<?php

declare(strict_types=1);

namespace App\Modules\Academics\Http\Requests\Institution;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Guards approve/revoke of a teacher: the acting institution must own the
 * teacher's account. There is no Policy for `Identity\Models\User` inside this
 * module, so ownership is checked here.
 */
final class TeacherApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        $teacher = $this->route('teacher');

        $institutionId = $this->user()?->institution_id;

        return $teacher !== null
            && $institutionId !== null
            && $teacher->institution_id === $institutionId;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }
}
