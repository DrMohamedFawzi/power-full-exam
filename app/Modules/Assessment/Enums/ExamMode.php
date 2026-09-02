<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Enums;

enum ExamMode: string
{
    /** Counts towards the student's record; full proctoring. */
    case Official = 'official';

    /** Practice run created by a teacher; proctored but not recorded as a result. */
    case MockTeacher = 'mock_teacher';

    /** Self-study practice created by the student; no proctoring. */
    case MockStudent = 'mock_student';

    public function label(): string
    {
        return match ($this) {
            self::Official => 'رسمي',
            self::MockTeacher => 'تجريبي (المعلّم)',
            self::MockStudent => 'تدريب ذاتي',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Official => 'primary',
            self::MockTeacher => 'info',
            self::MockStudent => 'neutral',
        };
    }

    public function isProctored(): bool
    {
        return $this !== self::MockStudent;
    }

    public function isRecorded(): bool
    {
        return $this === self::Official;
    }
}
