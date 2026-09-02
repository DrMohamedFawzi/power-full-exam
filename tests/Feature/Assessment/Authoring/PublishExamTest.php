<?php

declare(strict_types=1);

namespace Tests\Feature\Assessment\Authoring;

use App\Modules\Assessment\Enums\ExamStatus;
use App\Modules\Assessment\Models\Exam;
use App\Modules\Assessment\Models\Question;
use App\Modules\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublishExamTest extends TestCase
{
    use RefreshDatabase;

    public function test_publishing_fails_when_the_exam_has_no_questions(): void
    {
        $teacher = User::factory()->teacher()->create(['is_approved' => true]);
        $exam = Exam::factory()->draft()->create(['created_by' => $teacher->id]);

        $response = $this->actingAs($teacher)->post(route('teacher.exams.publish', $exam));

        $response->assertSessionHasErrors('exam');
        $this->assertSame(ExamStatus::Draft, $exam->fresh()->status);
    }

    public function test_publishing_fails_when_a_question_has_no_correct_answer(): void
    {
        $teacher = User::factory()->teacher()->create(['is_approved' => true]);
        $exam = Exam::factory()->draft()->create(['created_by' => $teacher->id]);
        Question::factory()->for($exam)->position(1)->create(['correct_answer' => []]);

        $response = $this->actingAs($teacher)->post(route('teacher.exams.publish', $exam));

        $response->assertSessionHasErrors('exam');
        $this->assertSame(ExamStatus::Draft, $exam->fresh()->status);
    }

    public function test_publishing_succeeds_for_a_coherent_exam(): void
    {
        $teacher = User::factory()->teacher()->create(['is_approved' => true]);
        $exam = Exam::factory()->draft()->create(['created_by' => $teacher->id]);
        Question::factory()->for($exam)->position(1)->create(['points' => 1, 'correct_answer' => ['أ']]);

        $response = $this->actingAs($teacher)->post(route('teacher.exams.publish', $exam));

        $response->assertRedirect(route('teacher.exams.show', $exam));
        $this->assertSame(ExamStatus::Published, $exam->fresh()->status);
    }

    public function test_teacher_can_close_a_published_exam(): void
    {
        $teacher = User::factory()->teacher()->create(['is_approved' => true]);
        $exam = Exam::factory()->create(['created_by' => $teacher->id]);

        $response = $this->actingAs($teacher)->post(route('teacher.exams.close', $exam));

        $response->assertRedirect(route('teacher.exams.show', $exam));
        $this->assertSame(ExamStatus::Closed, $exam->fresh()->status);
    }

    public function test_a_closed_exam_cannot_be_edited(): void
    {
        $teacher = User::factory()->teacher()->create(['is_approved' => true]);
        $exam = Exam::factory()->closed()->create(['created_by' => $teacher->id]);

        $this->actingAs($teacher)->get(route('teacher.exams.edit', $exam))->assertForbidden();
    }
}
