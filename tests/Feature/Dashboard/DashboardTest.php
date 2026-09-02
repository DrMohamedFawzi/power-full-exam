<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use App\Modules\Identity\Models\Institution;
use App\Modules\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The dashboards read across every module, so rendering them is the closest
 * thing this suite has to an end-to-end integration check: if any module's
 * models, enums or relations drift, one of these three pages stops rendering.
 */
final class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_dashboard_renders(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee($student->official_name);
    }

    public function test_teacher_dashboard_renders(): void
    {
        $teacher = User::factory()->teacher()->create(['is_approved' => true]);

        $this->actingAs($teacher)
            ->get(route('teacher.dashboard'))
            ->assertOk()
            ->assertSee($teacher->official_name);
    }

    public function test_institution_dashboard_renders(): void
    {
        $institution = Institution::factory()->create();
        $admin = User::factory()->institution()->create([
            'institution_id' => $institution->id,
        ]);

        $this->actingAs($admin)
            ->get(route('institution.dashboard'))
            ->assertOk();
    }

    public function test_each_dashboard_rejects_the_wrong_role(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)->get(route('teacher.dashboard'))->assertForbidden();
        $this->actingAs($student)->get(route('institution.dashboard'))->assertForbidden();
    }

    public function test_dashboards_require_authentication(): void
    {
        $this->get(route('student.dashboard'))->assertRedirect();
    }

    public function test_unapproved_teacher_is_held_at_the_pending_page(): void
    {
        $teacher = User::factory()->teacher()->create(['is_approved' => false]);

        $this->actingAs($teacher)
            ->get(route('teacher.dashboard'))
            ->assertRedirect(route('teacher.pending'));
    }
}
