<?php

declare(strict_types=1);

namespace App\Modules\Identity\Policies;

use App\Modules\Identity\Models\User;

final class UserPolicy
{
    public function update(User $user, User $target): bool
    {
        return $user->id === $target->id;
    }
}
