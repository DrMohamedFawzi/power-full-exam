<?php

declare(strict_types=1);

namespace Tests\Feature\Assessment\Authoring;

use App\Modules\Assessment\Enums\SessionStatus;
use App\Modules\Assessment\Models\Exam;
use App\Modules\Assessment\Models\ExamSession;
use App\Modules\Assessment\Models\Question;
use App\Modules\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_add_a_question_to_their_draft_exam(): void
    {
        $teacher = User::factory()->teacher()->create(['is_approved' => true]);
        $exam = Exam::factory()->draft()->create(['created_by' => $teacher->id]);

        $response = $this->actingAs($teacher)->postJson(route('teacher.exams.questions.store', $exam), [
            'type' => 'multiple_choice',
            'prompt' => 'ما ناتج 2 + 2؟',
            'options' => ['3', '4', '5'],
            'correct_answer' => [1],
            'explanation' => 'لأن 2 + 2 = 4',
            'points' => 2,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('question.prompt', 'ما ناتج 2 + 2؟');
        $this->assertDatabaseHas('questions', ['exam_id' => $exam->id, 'position' => 1]);
    }

    public function test_correct_answer_never_appears_in_a_student_facing_payload(): void
    {
        $question = Question::factory()->create(['correct_answer' => ['secret-answer']]);

        $array = $question->toArray();

        $this->assertArrayNotHasKey('correct_answer', $array);
        $this->assertArrayNotHasKey('explanation', $array);
        $this->assertStringNotContainsString('secret-answer', (string) json_encode($question));
    }

    public function test_teacher_can_reorder_questions(): void
    {
        $teacher = User::factory()->teacher()->create(['is_approved' => true]);
        $exam = Exam::factory()->draft()->create(['created_by' => $teacher->id]);
        $first = Question::factory()->for($exam)->position(1)->create();
        $second = Question::factory()->for($exam)->position(2)->create();

        $response = $this->actingAs($teacher)->putJson(route('teacher.exams.questions.reorder', $exam), [
            'question_ids' => [$second->id, $first->id],
        ]);

        $response->assertNoContent();
        $this->assertSame(1, $second->fresh()->position);
        $this->assertSame(2, $first->fresh()->position);
    }

    public function test_teacher_can_duplicate_a_question(): void
    {
        $teacher = User::factory()->teacher()->create(['is_approved' => true]);
        $exam = Exam::factory()->draft()->create(['created_by' => $teacher->id]);
        $question = Question::factory()->for($exam)->position(1)->create();

        $response = $this->actingAs($teacher)->postJson(route('teacher.exams.questions.duplicate', [$exam, $question]));

        $response->assertCreated();
        $this->assertSame(2, Question::query()->where('exam_id', $exam->id)->count());
    }

    public function test_teacher_can_delete_a_question(): void
    {
        $teacher = User::factory()->teacher()->create(['is_approved' => true]);
        $exam = Exam::factory()->draft()->create(['created_by' => $teacher->id]);
        $question = Question::factory()->for($exam)->position(1)->create();

        $response = $this->actingAs($teacher)->deleteJson(route('teacher.exams.questions.destroy', [$exam, $question]));

        $response->assertNoContent();
        $this->assertModelMissing($question);
    }

    public function test_questions_become_immutable_once_a_session_exists(): void
    {
        $teacher = User::factory()->teacher()->create(['is_approved' => true]);
        $exam = Exam::factory()->create(['created_by' => $teacher->id]);
        $question = Question::factory()->for($exam)->position(1)->create();

        ExamSession::factory()->for($exam)->status(SessionStatus::Active)->create();

        $response = $this->actingAs($teacher)->putJson(route('teacher.exams.questions.update', [$exam, $question]), [
            'type' => 'multiple_choice',
            'prompt' => 'محاولة تعديل بعد بدء المحاولات',
            'options' => ['أ', 'ب'],
            'correct_answer' => [0],
            'points' => 1,
        ]);

        $response->assertForbidden();
        $this->assertNotSame('محاولة تعديل بعد بدء المحاولات', $question->fresh()->prompt);
    }
}
