<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Queries;

use App\Modules\Assessment\Models\Exam;
use App\Modules\Identity\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

final class TeacherExamsQuery
{
    public function __invoke(User $teacher): LengthAwarePaginator
    {
        return Exam::query()
            ->where('created_by', $teacher->id)
            ->withCount(['questions', 'sessions'])
            ->with('classroom:id,name')
            ->latest()
            ->paginate(20)
            ->withQueryString();
    }
}
