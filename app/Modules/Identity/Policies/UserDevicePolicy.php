<?php

declare(strict_types=1);

namespace App\Modules\Identity\Policies;

use App\Modules\Identity\Models\User;
use App\Modules\Identity\Models\UserDevice;

final class UserDevicePolicy
{
    public function view(User $user, UserDevice $device): bool
    {
        return $user->id === $device->user_id;
    }

    public function revoke(User $user, UserDevice $device): bool
    {
        return $user->id === $device->user_id;
    }
}
