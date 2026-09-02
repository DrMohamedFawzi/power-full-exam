<?php

declare(strict_types=1);

namespace App\Modules\Academics\Actions;

use App\Modules\Academics\Enums\EnrollmentStatus;
use App\Modules\Academics\Models\EnrollmentRequest;
use App\Modules\Identity\Models\User;

final class ApproveEnrollmentRequest
{
    public function __invoke(EnrollmentRequest $request, User $reviewer): EnrollmentRequest
    {
        $request->update([
            'status' => EnrollmentStatus::Approved,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ]);

        return $request->refresh();
    }
}
