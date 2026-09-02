<?php

declare(strict_types=1);

namespace App\Modules\Proctoring\Queries;

use App\Modules\Assessment\Models\Exam;
use App\Modules\Identity\Models\User;

/** Exams owned by this teacher that currently have at least one active session. */
final class TeacherActiveExamsQuery
{
    /** @return array<int, array<string, mixed>> */
    public function __invoke(User $teacher): array
    {
        return Exam::query()
            ->where('created_by', $teacher->id)
            ->withCount(['sessions as active_sessions_count' => fn ($q) => $q->where('status', 'active')])
            ->having('active_sessions_count', '>', 0)
            ->get()
            ->map(fn (Exam $exam): array => [
                'id' => $exam->id,
                'title' => $exam->title,
                'code' => $exam->code,
                'security_level' => ['label' => $exam->security_level->label(), 'color' => $exam->security_level->color()],
                'active_sessions_count' => $exam->active_sessions_count,
            ])
            ->all();
    }
}
