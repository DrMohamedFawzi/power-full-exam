<?php

declare(strict_types=1);

namespace App\Support\Enums;

enum Severity: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'منخفضة',
            self::Medium => 'متوسطة',
            self::High => 'مرتفعة',
            self::Critical => 'حرجة',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Low => 'info',
            self::Medium => 'warning',
            self::High => 'error',
            self::Critical => 'error',
        };
    }

    /** Points deducted from the integrity index. */
    public function penalty(): int
    {
        return (int) config("aegis.proctoring.penalties.{$this->value}", 5);
    }
}
