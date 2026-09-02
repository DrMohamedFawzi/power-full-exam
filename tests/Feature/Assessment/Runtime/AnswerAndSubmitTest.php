<?php

declare(strict_types=1);

namespace Tests\Feature\Assessment\Runtime;

use App\Modules\Assessment\Actions\ExpireExamSession;
use App\Modules\Assessment\Actions\SaveAnswer;
use App\Modules\Assessment\Actions\SubmitExamSession;
use App\Modules\Assessment\Enums\QuestionType;
use App\Modules\Assessment\Enums\SessionStatus;
use App\Modules\Assessment\Models\Exam;
use App\Modules\Assessment\Models\ExamAnswer;
use App\Modules\Assessment\Models\ExamSession;
use App\Modules\Assessment\Models\Question;
use App\Modules\Assessment\Queries\ExamRunnerQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AnswerAndSubmitTest extends TestCase
{
    use RefreshDatabase;

    public function test_save_answer_upserts_idempotently(): void
    {
        $session = ExamSession::factory()->create();
        $question = Question::factory()->create(['exam_id' => $session->exam_id]);

        $action = app(SaveAnswer::class);
        $action($session, $question->id, ['أ'], 5);
        $action($session, $question->id, ['ب'], 12);

        $this->assertSame(1, ExamAnswer::query()->count());

        $answer = ExamAnswer::query()->first();
        $this->assertSame(['ب'], $answer->answer);
        $this->assertSame(12, $answer->time_spent_seconds);
    }

    public function test_submit_auto_grades_correct_and_incorrect_answers(): void
    {
        $exam = Exam::factory()->create();
        $session = ExamSession::factory()->create(['exam_id' => $exam->id]);

        $correctMcq = Question::factory()->type(QuestionType::MultipleChoice)->create([
            'exam_id' => $exam->id,
            'correct_answer' => ['أ'],
            'points' => 2,
        ]);
        $wrongTrueFalse = Question::factory()->trueFalse(true)->create(['exam_id' => $exam->id, 'points' => 1]);
        $shortAnswer = Question::factory()->shortAnswer()->create(['exam_id' => $exam->id, 'points' => 3]);

        app(SaveAnswer::class)($session, $correctMcq->id, ['أ'], 10);
        app(SaveAnswer::class)($session, $wrongTrueFalse->id, ['خطأ'], 10);
        app(SaveAnswer::class)($session, $shortAnswer->id, ['أي إجابة'], 10);

        $submitted = app(SubmitExamSession::class)($session);

        $this->assertSame(SessionStatus::Submitted, $submitted->status);
        $this->assertNotNull($submitted->submitted_at);
        $this->assertSame(2.0, (float) $submitted->score);

        $this->assertDatabaseHas('exam_answers', ['question_id' => $correctMcq->id, 'is_correct' => 1, 'points_awarded' => 2]);
        $this->assertDatabaseHas('exam_answers', ['question_id' => $wrongTrueFalse->id, 'is_correct' => 0, 'points_awarded' => 0]);

        $shortAnswerRow = ExamAnswer::query()->where('question_id', $shortAnswer->id)->first();
        $this->assertNull($shortAnswerRow->is_correct);
    }

    public function test_expired_session_auto_submits_whatever_was_saved(): void
    {
        $exam = Exam::factory()->create();
        $session = ExamSession::factory()->expired()->create(['exam_id' => $exam->id]);
        $question = Question::factory()->create(['exam_id' => $exam->id, 'correct_answer' => ['أ'], 'points' => 5]);

        app(SaveAnswer::class)($session, $question->id, ['أ'], 3);

        $result = app(ExpireExamSession::class)->ifDue($session);

        $this->assertSame(SessionStatus::Expired, $result->status);
        $this->assertSame(5.0, (float) $result->score);
    }

    public function test_correct_answers_never_reach_the_runner_payload(): void
    {
        $exam = Exam::factory()->create();
        $question = Question::factory()->create(['exam_id' => $exam->id, 'correct_answer' => ['secret-answer']]);

        $this->assertArrayNotHasKey('correct_answer', $question->toArray());

        $query = app(ExamRunnerQuery::class);
        $session = ExamSession::factory()->create(['exam_id' => $exam->id]);

        $payload = $query($session);
        $encoded = json_encode($payload);

        $this->assertStringNotContainsString('secret-answer', (string) $encoded);
    }
}
