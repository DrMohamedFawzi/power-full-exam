<?php

declare(strict_types=1);

namespace App\Modules\Academics\Actions;

use App\Modules\Academics\Enums\EnrollmentStatus;
use App\Modules\Academics\Models\Classroom;
use App\Modules\Academics\Models\EnrollmentRequest;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * A student joins a classroom by its code. Duplicate pending/approved requests
 * are rejected with a clear message; a previously rejected request may be
 * resubmitted.
 */
final class SubmitEnrollmentRequest
{
    public function __invoke(User $student, string $code): EnrollmentRequest
    {
        return DB::transaction(function () use ($student, $code): EnrollmentRequest {
            $classroom = Classroom::query()
                ->active()
                ->where('code', mb_strtoupper($code))
                ->lockForUpdate()
                ->first();

            if ($classroom === null) {
                throw ValidationException::withMessages([
                    'code' => 'لم يتم العثور على صف بهذا الرمز.',
                ]);
            }

            $existing = EnrollmentRequest::query()
                ->where('student_id', $student->id)
                ->where('classroom_id', $classroom->id)
                ->lockForUpdate()
                ->first();

            if ($existing !== null && $existing->status === EnrollmentStatus::Pending) {
                throw ValidationException::withMessages([
                    'code' => 'لديك طلب انضمام قيد المراجعة لهذا الصف بالفعل.',
                ]);
            }

            if ($existing !== null && $existing->status === EnrollmentStatus::Approved) {
                throw ValidationException::withMessages([
                    'code' => 'أنت منضم إلى هذا الصف بالفعل.',
                ]);
            }

            if ($existing !== null) {
                $existing->update([
                    'status' => EnrollmentStatus::Pending,
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                    'rejection_reason' => null,
                ]);

                return $existing->refresh();
            }

            return EnrollmentRequest::query()->create([
                'student_id' => $student->id,
                'classroom_id' => $classroom->id,
                'status' => EnrollmentStatus::Pending,
            ]);
        });
    }
}
