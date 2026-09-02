<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Enums;

/**
 * How aggressively the runner locks the exam down. Drives which client-side
 * proctoring monitors are armed and how violations are punished.
 */
enum SecurityLevel: string
{
    case Off = 'off';
    case Moderate = 'moderate';
    case Strict = 'strict';

    public function label(): string
    {
        return match ($this) {
            self::Off => 'بدون مراقبة',
            self::Moderate => 'مراقبة متوسطة',
            self::Strict => 'مراقبة صارمة',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Off => 'neutral',
            self::Moderate => 'warning',
            self::Strict => 'error',
        };
    }

    public function requiresDeviceBinding(): bool
    {
        return $this === self::Strict;
    }

    public function blocksOnViolation(): bool
    {
        return $this === self::Strict;
    }

    /**
     * Client monitors armed at this level.
     *
     * @return list<string>
     */
    public function monitors(): array
    {
        return match ($this) {
            self::Off => [],
            self::Moderate => ['visibility', 'fullscreen', 'connectivity', 'copy-paste', 'ios-block'],
            self::Strict => [
                'visibility', 'fullscreen', 'connectivity', 'copy-paste',
                'devtools', 'multi-display', 'keystroke', 'vision', 'audio', 'ios-block',
            ],
        };
    }
}
