<?php

declare(strict_types=1);

namespace App\Modules\Academics\Policies;

use App\Modules\Academics\Models\Classroom;
use App\Modules\Identity\Models\User;

final class ClassroomPolicy
{
    public function view(User $user, Classroom $classroom): bool
    {
        return $user->id === $classroom->teacher_id;
    }

    public function update(User $user, Classroom $classroom): bool
    {
        return $user->id === $classroom->teacher_id;
    }

    public function delete(User $user, Classroom $classroom): bool
    {
        return $user->id === $classroom->teacher_id;
    }
}
