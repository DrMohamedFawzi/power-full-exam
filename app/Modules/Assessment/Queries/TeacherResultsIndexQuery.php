<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Queries;

use App\Modules\Assessment\Enums\ExamStatus;
use App\Modules\Assessment\Models\Exam;
use App\Modules\Identity\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

/** Exams a teacher can inspect results for — anything that has left draft. */
final class TeacherResultsIndexQuery
{
    public function __invoke(User $teacher): LengthAwarePaginator
    {
        return Exam::query()
            ->where('created_by', $teacher->id)
            ->whereIn('status', [ExamStatus::Published->value, ExamStatus::Closed->value])
            ->withCount('sessions')
            ->latest()
            ->paginate(20)
            ->withQueryString();
    }
}
