<?php

declare(strict_types=1);

namespace App\Modules\Academics\Actions;

use App\Modules\Identity\Models\User;

final class ApproveTeacher
{
    public function __invoke(User $teacher): User
    {
        $teacher->update(['is_approved' => true]);

        return $teacher->refresh();
    }
}
