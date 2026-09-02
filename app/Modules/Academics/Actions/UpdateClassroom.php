<?php

declare(strict_types=1);

namespace App\Modules\Academics\Actions;

use App\Modules\Academics\Models\Classroom;

final class UpdateClassroom
{
    /** @param array{name: string, description: ?string, is_archived?: bool} $data */
    public function __invoke(Classroom $classroom, array $data): Classroom
    {
        $classroom->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_archived' => $data['is_archived'] ?? $classroom->is_archived,
        ]);

        return $classroom->refresh();
    }
}
