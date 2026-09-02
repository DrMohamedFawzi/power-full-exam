<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Queries;

use App\Modules\Assessment\Enums\ExamStatus;
use App\Modules\Assessment\Models\Exam;
use App\Modules\Identity\Models\Institution;
use Illuminate\Pagination\LengthAwarePaginator;

/** Exams run inside an institution's own classrooms, once they have left draft. */
final class InstitutionExamsQuery
{
    public function __invoke(Institution $institution): LengthAwarePaginator
    {
        return Exam::query()
            ->whereHas('classroom', fn ($query) => $query->where('institution_id', $institution->id))
            ->whereIn('status', [ExamStatus::Published->value, ExamStatus::Closed->value])
            ->with(['classroom:id,name', 'author:id,official_name'])
            ->withCount('sessions')
            ->latest()
            ->paginate(20)
            ->withQueryString();
    }
}
