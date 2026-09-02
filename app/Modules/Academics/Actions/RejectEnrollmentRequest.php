<?php

declare(strict_types=1);

namespace App\Modules\Academics\Actions;

use App\Modules\Academics\Enums\EnrollmentStatus;
use App\Modules\Academics\Models\EnrollmentRequest;
use App\Modules\Identity\Models\User;

final class RejectEnrollmentRequest
{
    public function __invoke(EnrollmentRequest $request, User $reviewer, ?string $reason = null): EnrollmentRequest
    {
        $request->update([
            'status' => EnrollmentStatus::Rejected,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);

        return $request->refresh();
    }
}
