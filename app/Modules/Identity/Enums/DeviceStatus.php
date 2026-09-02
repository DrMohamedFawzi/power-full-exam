<?php

declare(strict_types=1);

namespace App\Modules\Identity\Enums;

enum DeviceStatus: string
{
    case Active = 'active';
    case Revoked = 'revoked';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'نشط',
            self::Revoked => 'ملغى',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Revoked => 'error',
        };
    }
}
