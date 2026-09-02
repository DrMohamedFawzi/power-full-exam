<?php

declare(strict_types=1);

namespace Tests\Feature\Proctoring;

use App\Modules\Assessment\Enums\SessionStatus;
use App\Modules\Assessment\Models\Exam;
use App\Modules\Assessment\Models\ExamSession;
use App\Modules\Identity\Models\User;
use App\Modules\Proctoring\Jobs\ProcessProctoringSignal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class SignalIngestTest extends TestCase
{
    use RefreshDatabase;

    public function test_accepts_a_known_violation_type_and_queues_a_job(): void
    {
        Queue::fake();

        $exam = Exam::factory()->create();
        $session = ExamSession::factory()->create(['exam_id' => $exam->id]);

        $response = $this->actingAs($session->student)
            ->postJson(route('student.sessions.signal', $session), [
                'signals' => [
                    ['type' => 'tab_switch'],
                ],
            ]);

        $response->assertStatus(202);
        Queue::assertPushed(ProcessProctoringSignal::class);
    }

    public function test_rejects_an_unknown_signal_type(): void
    {
        Queue::fake();

        $exam = Exam::factory()->create();
        $session = ExamSession::factory()->create(['exam_id' => $exam->id]);

        $response = $this->actingAs($session->student)
            ->postJson(route('student.sessions.signal', $session), [
                'signals' => [
                    ['type' => 'made_up_violation'],
                ],
            ]);

        $response->assertStatus(422);
        Queue::assertNotPushed(ProcessProctoringSignal::class);
    }

    public function test_does_not_queue_signals_for_a_finished_session(): void
    {
        Queue::fake();

        $exam = Exam::factory()->create();
        $session = ExamSession::factory()->status(SessionStatus::Submitted)->create(['exam_id' => $exam->id]);

        $response = $this->actingAs($session->student)
            ->postJson(route('student.sessions.signal', $session), [
                'signals' => [
                    ['type' => 'tab_switch'],
                ],
            ]);

        $response->assertStatus(202);
        Queue::assertNotPushed(ProcessProctoringSignal::class);
    }

    public function test_a_student_cannot_signal_another_students_session(): void
    {
        Queue::fake();

        $exam = Exam::factory()->create();
        $session = ExamSession::factory()->create(['exam_id' => $exam->id]);
        $intruder = User::factory()->student()->create();

        $response = $this->actingAs($intruder)
            ->postJson(route('student.sessions.signal', $session), [
                'signals' => [['type' => 'tab_switch']],
            ]);

        $response->assertStatus(403);
    }
}
