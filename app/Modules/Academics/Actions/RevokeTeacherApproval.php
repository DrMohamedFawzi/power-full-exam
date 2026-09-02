<?php

declare(strict_types=1);

namespace App\Modules\Academics\Actions;

use App\Modules\Identity\Models\User;

final class RevokeTeacherApproval
{
    public function __invoke(User $teacher): User
    {
        $teacher->update(['is_approved' => false]);

        return $teacher->refresh();
    }
}
