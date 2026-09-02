<?php

declare(strict_types=1);

namespace App\Modules\Identity\Data;

use App\Modules\Identity\Models\UserDevice;

/**
 * Result of BindDevice — callers (the exam runner, in particular) need to know
 * whether this device was ever seen before to decide if a violation is due.
 */
final readonly class BindDeviceResult
{
    public function __construct(
        public UserDevice $device,
        public bool $isNewDevice,
    ) {}
}
