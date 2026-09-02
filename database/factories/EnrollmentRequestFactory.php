<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Academics\Enums\EnrollmentStatus;
use App\Modules\Academics\Models\Classroom;
use App\Modules\Academics\Models\EnrollmentRequest;
use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EnrollmentRequest>
 */
class EnrollmentRequestFactory extends Factory
{
    protected $model = EnrollmentRequest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => User::factory()->student(),
            'classroom_id' => Classroom::factory(),
            'status' => EnrollmentStatus::Pending->value,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'rejection_reason' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (): array => [
            'status' => EnrollmentStatus::Approved->value,
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (): array => [
            'status' => EnrollmentStatus::Rejected->value,
            'reviewed_at' => now(),
        ]);
    }
}
