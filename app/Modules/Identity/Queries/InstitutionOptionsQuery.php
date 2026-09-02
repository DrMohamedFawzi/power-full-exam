<?php

declare(strict_types=1);

namespace App\Modules\Identity\Queries;

use App\Modules\Identity\Models\Institution;

/**
 * Options for the registration form's institution picker (students, teachers).
 */
final class InstitutionOptionsQuery
{
    /** @return array<int, string> */
    public function __invoke(): array
    {
        return Institution::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
