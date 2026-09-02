<?php

declare(strict_types=1);

namespace App\Modules\Academics\Actions;

use App\Modules\Academics\Enums\EnrollmentStatus;
use App\Modules\Academics\Models\Classroom;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Approves every pending enrollment request for a classroom in one go, or a
 * given subset of request ids when provided.
 */
final class BulkApproveEnrollmentRequests
{
    /** @param array<int, int> $requestIds */
    public function __invoke(Classroom $classroom, User $reviewer, array $requestIds = []): int
    {
        return DB::transaction(function () use ($classroom, $reviewer, $requestIds): int {
            $query = $classroom->enrollmentRequests()
                ->where('status', EnrollmentStatus::Pending->value);

            if ($requestIds !== []) {
                $query->whereIn('id', $requestIds);
            }

            return $query->update([
                'status' => EnrollmentStatus::Approved->value,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'rejection_reason' => null,
            ]);
        });
    }
}
