<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Enums;

enum SessionStatus: string
{
    case Active = 'active';
    case Submitted = 'submitted';
    case Expired = 'expired';
    case Terminated = 'terminated';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'جارية',
            self::Submitted => 'مُسلَّمة',
            self::Expired => 'منتهية الوقت',
            self::Terminated => 'أُنهيت لمخالفة',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'info',
            self::Submitted => 'success',
            self::Expired => 'warning',
            self::Terminated => 'error',
        };
    }

    public function isFinished(): bool
    {
        return $this !== self::Active;
    }
}
