<?php

declare(strict_types=1);

namespace App\Modules\Academics\Actions;

use App\Modules\Academics\Models\Classroom;
use Illuminate\Support\Facades\DB;

/**
 * Hard-deletes a classroom when it has no exam history; otherwise archives it,
 * since exam records must keep referring to a real classroom row.
 */
final class DeleteClassroom
{
    public function __invoke(Classroom $classroom): bool
    {
        if ($this->hasExams($classroom)) {
            $classroom->update(['is_archived' => true]);

            return false;
        }

        $classroom->delete();

        return true;
    }

    private function hasExams(Classroom $classroom): bool
    {
        return DB::table('exams')->where('classroom_id', $classroom->id)->exists();
    }
}
