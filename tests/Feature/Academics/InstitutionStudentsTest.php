<?php

declare(strict_types=1);

namespace Tests\Feature\Academics;

use App\Modules\Academics\Models\Classroom;
use App\Modules\Academics\Models\EnrollmentRequest;
use App\Modules\Identity\Models\Institution;
use App\Modules\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstitutionStudentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_institution_can_see_its_students_roster_with_classroom_counts(): void
    {
        $institution = Institution::factory()->create();
        $institutionUser = User::factory()->institution()->create(['institution_id' => $institution->id]);

        $teacher = User::factory()->teacher()->create(['institution_id' => $institution->id, 'is_approved' => true]);
        $classroom = Classroom::factory()->for($teacher, 'teacher')->create(['institution_id' => $institution->id]);

        $student = User::factory()->student()->create([
            'institution_id' => $institution->id,
            'official_name' => 'طالب تجريبي فريد',
        ]);

        EnrollmentRequest::factory()->approved()->create([
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
        ]);

        $this->actingAs($institutionUser)
            ->get(route('institution.students.index'))
            ->assertOk()
            ->assertSee('طالب تجريبي فريد')
            ->assertSee('منضم');
    }

    public function test_student_roster_search_filters_by_name(): void
    {
        $institution = Institution::factory()->create();
        $institutionUser = User::factory()->institution()->create(['institution_id' => $institution->id]);

        User::factory()->student()->create([
            'institution_id' => $institution->id,
            'official_name' => 'أحمد المطابق',
        ]);
        User::factory()->student()->create([
            'institution_id' => $institution->id,
            'official_name' => 'شخص آخر تمامًا',
        ]);

        $this->actingAs($institutionUser)
            ->get(route('institution.students.index', ['search' => 'المطابق']))
            ->assertOk()
            ->assertSee('أحمد المطابق')
            ->assertDontSee('شخص آخر تمامًا');
    }
}
