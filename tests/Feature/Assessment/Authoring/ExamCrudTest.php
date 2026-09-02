<?php

declare(strict_types=1);

namespace Tests\Feature\Assessment\Authoring;

use App\Modules\Academics\Models\Classroom;
use App\Modules\Assessment\Enums\ExamStatus;
use App\Modules\Assessment\Models\Exam;
use App\Modules\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_create_a_draft_exam(): void
    {
        $teacher = User::factory()->teacher()->create(['is_approved' => true]);

        $response = $this->actingAs($teacher)->post(route('teacher.exams.store'), [
            'title' => 'اختبار الوحدة الأولى',
            'description' => 'اختبار تجريبي',
            'duration_minutes' => 45,
            'security_level' => 'strict',
            'mode' => 'official',
            'shuffle_questions' => '1',
            'preserve_time_offline' => '0',
            'max_attempts' => 1,
        ]);

        $exam = Exam::query()->first();

        $response->assertRedirect(route('teacher.exams.edit', $exam));
        $this->assertNotNull($exam);
        $this->assertSame('اختبار الوحدة الأولى', $exam->title);
        $this->assertSame(ExamStatus::Draft, $exam->status);
        $this->assertSame($teacher->id, $exam->created_by);
        $this->assertNotEmpty($exam->code);
    }

    public function test_teacher_cannot_create_exam_for_a_classroom_they_do_not_own(): void
    {
        $teacher = User::factory()->teacher()->create(['is_approved' => true]);
        $otherClassroom = Classroom::factory()->create();

        $response = $this->actingAs($teacher)->post(route('teacher.exams.store'), [
            'title' => 'اختبار',
            'classroom_id' => $otherClassroom->id,
            'duration_minutes' => 30,
            'security_level' => 'off',
            'mode' => 'official',
            'max_attempts' => 1,
        ]);

        $response->assertSessionHasErrors('classroom_id');
    }

    public function test_teacher_can_update_a_draft_exam(): void
    {
        $teacher = User::factory()->teacher()->create(['is_approved' => true]);
        $exam = Exam::factory()->draft()->create(['created_by' => $teacher->id]);

        $response = $this->actingAs($teacher)->put(route('teacher.exams.update', $exam), [
            'title' => 'عنوان محدّث',
            'duration_minutes' => 90,
            'security_level' => 'moderate',
            'mode' => 'official',
            'max_attempts' => 2,
        ]);

        $response->assertRedirect(route('teacher.exams.edit', $exam));
        $this->assertSame('عنوان محدّث', $exam->fresh()->title);
    }

    public function test_a_teacher_cannot_edit_another_teachers_exam(): void
    {
        $teacher = User::factory()->teacher()->create(['is_approved' => true]);
        $otherTeacher = User::factory()->teacher()->create(['is_approved' => true]);
        $exam = Exam::factory()->draft()->create(['created_by' => $otherTeacher->id]);

        $this->actingAs($teacher)->get(route('teacher.exams.edit', $exam))->assertForbidden();
    }

    public function test_teacher_can_delete_a_draft_exam_with_no_sessions(): void
    {
        $teacher = User::factory()->teacher()->create(['is_approved' => true]);
        $exam = Exam::factory()->draft()->create(['created_by' => $teacher->id]);

        $response = $this->actingAs($teacher)->delete(route('teacher.exams.destroy', $exam));

        $response->assertRedirect(route('teacher.exams.index'));
        $this->assertModelMissing($exam);
    }

    public function test_unapproved_teacher_is_redirected_away_from_authoring(): void
    {
        $teacher = User::factory()->teacher()->pending()->create();

        $this->actingAs($teacher)->get(route('teacher.exams.index'))->assertRedirect(route('teacher.pending'));
    }
}
