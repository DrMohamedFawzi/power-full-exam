<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Modules\Identity\Enums\DeviceStatus;
use App\Modules\Identity\Models\UserDevice;

final class RevokeDevice
{
    public function __invoke(UserDevice $device): UserDevice
    {
        $device->update(['status' => DeviceStatus::Revoked]);

        return $device;
    }
}
