<?php

declare(strict_types=1);

namespace App\Modules\Academics\Actions;

use App\Modules\Academics\Models\Classroom;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Str;

/**
 * Creates a classroom for a teacher, minting a unique join code.
 */
final class CreateClassroom
{
    /** @param array{name: string, description: ?string} $data */
    public function __invoke(User $teacher, array $data): Classroom
    {
        return Classroom::query()->create([
            'code' => $this->uniqueCode(),
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'teacher_id' => $teacher->id,
            'institution_id' => $teacher->institution_id,
            'is_archived' => false,
        ]);
    }

    private function uniqueCode(): string
    {
        do {
            $code = Str::upper(Str::random(6));
            $code = strtr($code, ['O' => '0', 'I' => '1']);
        } while (Classroom::query()->where('code', $code)->exists());

        return $code;
    }
}
