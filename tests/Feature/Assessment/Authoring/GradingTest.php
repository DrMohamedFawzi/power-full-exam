<?php

declare(strict_types=1);

namespace Tests\Feature\Assessment\Authoring;

use App\Modules\Assessment\Enums\SessionStatus;
use App\Modules\Assessment\Models\Exam;
use App\Modules\Assessment\Models\ExamAnswer;
use App\Modules\Assessment\Models\ExamSession;
use App\Modules\Assessment\Models\Question;
use App\Modules\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradingTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_grade_a_short_answer_and_it_recomputes_the_session_score(): void
    {
        $teacher = User::factory()->teacher()->create(['is_approved' => true]);
        $exam = Exam::factory()->create(['created_by' => $teacher->id]);

        $autoGraded = Question::factory()->for($exam)->position(1)->create(['points' => 2]);
        $shortAnswer = Question::factory()->for($exam)->position(2)->shortAnswer()->create(['points' => 3]);

        $session = ExamSession::factory()->for($exam)->status(SessionStatus::Submitted)->create(['score' => 2]);

        ExamAnswer::factory()->create([
            'exam_session_id' => $session->id,
            'question_id' => $autoGraded->id,
            'is_correct' => true,
            'points_awarded' => 2,
        ]);

        $answer = ExamAnswer::factory()->create([
            'exam_session_id' => $session->id,
            'question_id' => $shortAnswer->id,
            'answer' => ['إجابة الطالب'],
            'is_correct' => null,
            'points_awarded' => 0,
        ]);

        $response = $this->actingAs($teacher)->patch(route('teacher.answers.grade', $answer), [
            'points_awarded' => 3,
        ]);

        $response->assertRedirect();
        $this->assertSame(3.0, (float) $answer->fresh()->points_awarded);
        $this->assertTrue($answer->fresh()->is_correct);
        $this->assertSame(5.0, (float) $session->fresh()->score);
    }

    public function test_grade_awarded_is_clamped_to_the_questions_max_points(): void
    {
        $teacher = User::factory()->teacher()->create(['is_approved' => true]);
        $exam = Exam::factory()->create(['created_by' => $teacher->id]);
        $question = Question::factory()->for($exam)->position(1)->shortAnswer()->create(['points' => 2]);
        $session = ExamSession::factory()->for($exam)->create();

        $answer = ExamAnswer::factory()->create([
            'exam_session_id' => $session->id,
            'question_id' => $question->id,
            'is_correct' => null,
            'points_awarded' => 0,
        ]);

        $this->actingAs($teacher)->patch(route('teacher.answers.grade', $answer), ['points_awarded' => 100]);

        $this->assertSame(2.0, (float) $answer->fresh()->points_awarded);
    }

    public function test_a_teacher_cannot_grade_answers_on_another_teachers_exam(): void
    {
        $teacher = User::factory()->teacher()->create(['is_approved' => true]);
        $otherTeacher = User::factory()->teacher()->create(['is_approved' => true]);
        $exam = Exam::factory()->create(['created_by' => $otherTeacher->id]);
        $question = Question::factory()->for($exam)->position(1)->shortAnswer()->create();
        $session = ExamSession::factory()->for($exam)->create();

        $answer = ExamAnswer::factory()->create([
            'exam_session_id' => $session->id,
            'question_id' => $question->id,
        ]);

        $this->actingAs($teacher)
            ->patch(route('teacher.answers.grade', $answer), ['points_awarded' => 1])
            ->assertForbidden();
    }
}
