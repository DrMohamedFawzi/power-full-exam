<?php

declare(strict_types=1);

namespace App\Support\Enums;

enum Role: string
{
    case Student = 'student';
    case Teacher = 'teacher';
    case Institution = 'institution';

    public function label(): string
    {
        return match ($this) {
            self::Student => 'طالب',
            self::Teacher => 'معلّم',
            self::Institution => 'مؤسسة',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Student => 'info',
            self::Teacher => 'primary',
            self::Institution => 'secondary',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Student => 'academic-cap',
            self::Teacher => 'presentation-chart-bar',
            self::Institution => 'building-library',
        };
    }

    /** Landing route after login. */
    public function homeRoute(): string
    {
        return match ($this) {
            self::Student => 'student.dashboard',
            self::Teacher => 'teacher.dashboard',
            self::Institution => 'institution.dashboard',
        };
    }
}
