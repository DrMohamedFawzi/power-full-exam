<?php

declare(strict_types=1);

namespace Tests\Feature\Proctoring;

use App\Modules\Assessment\Models\Exam;
use App\Modules\Assessment\Models\ExamSession;
use App\Modules\Proctoring\Enums\ViolationType;
use App\Modules\Proctoring\Models\KeystrokeSample;
use App\Modules\Proctoring\Models\Violation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * End-to-end through the queue (QUEUE_CONNECTION=sync in testing, so
 * dispatch() runs the job inline) rather than mocking each collaborator.
 */
final class ProcessProctoringSignalPipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_plain_violation_signal_is_recorded_and_the_index_recalculated(): void
    {
        $exam = Exam::factory()->create();
        $session = ExamSession::factory()->create(['exam_id' => $exam->id, 'integrity_index' => 100]);

        $this->actingAs($session->student)->postJson(route('student.sessions.signal', $session), [
            'signals' => [['type' => 'tab_switch']],
        ])->assertStatus(202);

        $this->assertSame(1, Violation::query()->where('exam_session_id', $session->id)->count());
        $this->assertLessThan(100, $session->fresh()->integrity_index);
    }

    public function test_a_keystroke_signal_only_becomes_a_violation_when_anomalous(): void
    {
        $exam = Exam::factory()->create();
        $session = ExamSession::factory()->create(['exam_id' => $exam->id]);

        // Baseline windows — consistent rhythm, never anomalous.
        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($session->student)->postJson(route('student.sessions.signal', $session), [
                'signals' => [[
                    'type' => 'keystroke_anomaly',
                    'metadata' => ['intervals' => [150, 152, 148, 151, 149]],
                ]],
            ])->assertStatus(202);
        }

        $this->assertSame(0, Violation::query()->where('exam_session_id', $session->id)->count());
        $this->assertSame(3, KeystrokeSample::query()->where('exam_session_id', $session->id)->count());

        // A sharply different rhythm — flagged as an anomaly against the baseline.
        $this->actingAs($session->student)->postJson(route('student.sessions.signal', $session), [
            'signals' => [[
                'type' => 'keystroke_anomaly',
                'metadata' => ['intervals' => [15, 12, 18, 14, 16]],
            ]],
        ])->assertStatus(202);

        $this->assertSame(1, Violation::query()
            ->where('exam_session_id', $session->id)
            ->where('type', ViolationType::KeystrokeAnomaly->value)
            ->count());
    }
}
