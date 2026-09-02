<?php

declare(strict_types=1);

namespace Tests\Feature\Academics;

use App\Modules\Academics\Enums\EnrollmentStatus;
use App\Modules\Academics\Models\Classroom;
use App\Modules\Academics\Models\EnrollmentRequest;
use App\Modules\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_join_a_classroom_by_code(): void
    {
        $classroom = Classroom::factory()->create(['code' => 'ABC123']);
        $student = User::factory()->student()->create();

        $this->actingAs($student)
            ->post(route('student.classrooms.join'), ['code' => 'abc123'])
            ->assertRedirect();

        $this->assertDatabaseHas('enrollment_requests', [
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'status' => EnrollmentStatus::Pending->value,
        ]);
    }

    public function test_joining_with_an_unknown_code_fails_gracefully(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)
            ->post(route('student.classrooms.join'), ['code' => 'ZZZZZZ'])
            ->assertSessionHasErrors('code');

        $this->assertDatabaseCount('enrollment_requests', 0);
    }

    public function test_a_pending_duplicate_join_request_is_rejected(): void
    {
        $classroom = Classroom::factory()->create(['code' => 'DUP001']);
        $student = User::factory()->student()->create();

        EnrollmentRequest::factory()->create([
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
            'status' => EnrollmentStatus::Pending->value,
        ]);

        $this->actingAs($student)
            ->post(route('student.classrooms.join'), ['code' => 'DUP001'])
            ->assertSessionHasErrors('code');

        $this->assertDatabaseCount('enrollment_requests', 1);
    }

    public function test_a_rejected_request_can_be_resubmitted(): void
    {
        $classroom = Classroom::factory()->create(['code' => 'REJ001']);
        $student = User::factory()->student()->create();

        $rejected = EnrollmentRequest::factory()->rejected()->create([
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
        ]);

        $this->actingAs($student)
            ->post(route('student.classrooms.join'), ['code' => 'REJ001'])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseCount('enrollment_requests', 1);
        $this->assertSame(
            EnrollmentStatus::Pending,
            $rejected->fresh()->status,
        );
    }

    public function test_teacher_can_approve_an_enrollment_request(): void
    {
        $teacher = User::factory()->teacher()->create(['is_approved' => true]);
        $classroom = Classroom::factory()->for($teacher, 'teacher')->create();
        $request = EnrollmentRequest::factory()->create(['classroom_id' => $classroom->id]);

        $this->actingAs($teacher)
            ->post(route('teacher.enrollments.approve', $request))
            ->assertRedirect();

        $request->refresh();
        $this->assertSame(EnrollmentStatus::Approved, $request->status);
        $this->assertSame($teacher->id, $request->reviewed_by);
        $this->assertNotNull($request->reviewed_at);
    }

    public function test_teacher_can_reject_an_enrollment_request_with_a_reason(): void
    {
        $teacher = User::factory()->teacher()->create(['is_approved' => true]);
        $classroom = Classroom::factory()->for($teacher, 'teacher')->create();
        $request = EnrollmentRequest::factory()->create(['classroom_id' => $classroom->id]);

        $this->actingAs($teacher)
            ->post(route('teacher.enrollments.reject', $request), ['reason' => 'غير مسجل في هذا الصف'])
            ->assertRedirect();

        $request->refresh();
        $this->assertSame(EnrollmentStatus::Rejected, $request->status);
        $this->assertSame('غير مسجل في هذا الصف', $request->rejection_reason);
    }

    public function test_a_teacher_cannot_review_another_teachers_enrollment_request(): void
    {
        $owner = User::factory()->teacher()->create(['is_approved' => true]);
        $stranger = User::factory()->teacher()->create(['is_approved' => true]);
        $classroom = Classroom::factory()->for($owner, 'teacher')->create();
        $request = EnrollmentRequest::factory()->create(['classroom_id' => $classroom->id]);

        $this->actingAs($stranger)
            ->post(route('teacher.enrollments.approve', $request))
            ->assertForbidden();

        $this->assertSame(EnrollmentStatus::Pending, $request->fresh()->status);
    }

    public function test_teacher_can_bulk_approve_pending_requests_for_a_classroom(): void
    {
        $teacher = User::factory()->teacher()->create(['is_approved' => true]);
        $classroom = Classroom::factory()->for($teacher, 'teacher')->create();
        EnrollmentRequest::factory()->count(3)->create(['classroom_id' => $classroom->id]);

        $this->actingAs($teacher)
            ->post(route('teacher.enrollments.bulk-approve', $classroom))
            ->assertRedirect();

        $this->assertDatabaseCount('enrollment_requests', 3);
        $this->assertSame(
            3,
            EnrollmentRequest::query()->where('status', EnrollmentStatus::Approved->value)->count(),
        );
    }
}
