<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Enums;

enum QuestionType: string
{
    case MultipleChoice = 'multiple_choice';
    case MultipleSelect = 'multiple_select';
    case TrueFalse = 'true_false';
    case ShortAnswer = 'short_answer';

    public function label(): string
    {
        return match ($this) {
            self::MultipleChoice => 'اختيار من متعدد',
            self::MultipleSelect => 'اختيار متعدد الإجابات',
            self::TrueFalse => 'صح أو خطأ',
            self::ShortAnswer => 'إجابة قصيرة',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::MultipleChoice => 'primary',
            self::MultipleSelect => 'secondary',
            self::TrueFalse => 'info',
            self::ShortAnswer => 'accent',
        };
    }

    public function hasOptions(): bool
    {
        return in_array($this, [self::MultipleChoice, self::MultipleSelect, self::TrueFalse], true);
    }

    /** Short answers need a teacher's eye; the rest grade themselves. */
    public function isAutoGradable(): bool
    {
        return $this !== self::ShortAnswer;
    }
}
