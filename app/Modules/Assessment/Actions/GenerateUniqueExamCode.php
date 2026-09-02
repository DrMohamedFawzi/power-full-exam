<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Actions;

use App\Modules\Assessment\Models\Exam;
use Illuminate\Support\Str;

/** Short, human-typeable, guaranteed-unique exam join code. */
final class GenerateUniqueExamCode
{
    public function __invoke(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (Exam::query()->where('code', $code)->exists());

        return $code;
    }
}
