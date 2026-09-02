<?php

declare(strict_types=1);

namespace App\Modules\Academics\Policies;

use App\Modules\Academics\Models\EnrollmentRequest;
use App\Modules\Identity\Models\User;

final class EnrollmentRequestPolicy
{
    public function view(User $user, EnrollmentRequest $request): bool
    {
        return $user->id === $request->student_id
            || $user->id === $request->classroom->teacher_id;
    }

    /** Approve/reject share the same authorization: own the classroom. */
    public function review(User $user, EnrollmentRequest $request): bool
    {
        return $user->id === $request->classroom->teacher_id;
    }
}
