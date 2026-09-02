<?php

declare(strict_types=1);

namespace Tests\Feature\Academics;

use App\Modules\Identity\Models\Institution;
use App\Modules\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_institution_can_approve_a_pending_teacher(): void
    {
        $institution = Institution::factory()->create();
        $institutionUser = User::factory()->institution()->create(['institution_id' => $institution->id]);
        $teacher = User::factory()->teacher()->pending()->create(['institution_id' => $institution->id]);

        $this->actingAs($institutionUser)
            ->post(route('institution.teachers.approve', $teacher))
            ->assertRedirect();

        $this->assertTrue($teacher->fresh()->is_approved);
    }

    public function test_institution_can_revoke_an_approved_teacher(): void
    {
        $institution = Institution::factory()->create();
        $institutionUser = User::factory()->institution()->create(['institution_id' => $institution->id]);
        $teacher = User::factory()->teacher()->create(['institution_id' => $institution->id, 'is_approved' => true]);

        $this->actingAs($institutionUser)
            ->post(route('institution.teachers.revoke', $teacher))
            ->assertRedirect();

        $this->assertFalse($teacher->fresh()->is_approved);
    }

    public function test_an_institution_cannot_approve_another_institutions_teacher(): void
    {
        $institutionA = Institution::factory()->create();
        $institutionB = Institution::factory()->create();

        $institutionUserA = User::factory()->institution()->create(['institution_id' => $institutionA->id]);
        $teacherOfB = User::factory()->teacher()->pending()->create(['institution_id' => $institutionB->id]);

        $this->actingAs($institutionUserA)
            ->post(route('institution.teachers.approve', $teacherOfB))
            ->assertForbidden();

        $this->assertFalse($teacherOfB->fresh()->is_approved);
    }

    public function test_teachers_index_lists_pending_teachers_first(): void
    {
        $institution = Institution::factory()->create();
        $institutionUser = User::factory()->institution()->create(['institution_id' => $institution->id]);
        User::factory()->teacher()->create(['institution_id' => $institution->id, 'is_approved' => true]);
        User::factory()->teacher()->pending()->create(['institution_id' => $institution->id]);

        $this->actingAs($institutionUser)
            ->get(route('institution.teachers.index'))
            ->assertOk()
            ->assertSee('بانتظار الاعتماد');
    }
}
