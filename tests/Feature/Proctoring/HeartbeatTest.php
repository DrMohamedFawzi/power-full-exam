<?php

declare(strict_types=1);

namespace Tests\Feature\Proctoring;

use App\Modules\Assessment\Models\Exam;
use App\Modules\Assessment\Models\ExamSession;
use App\Modules\Proctoring\Actions\RecordHeartbeat;
use App\Modules\Proctoring\Enums\ViolationType;
use App\Modules\Proctoring\Jobs\ProcessProctoringSignal;
use App\Modules\Proctoring\Models\Heartbeat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class HeartbeatTest extends TestCase
{
    use RefreshDatabase;

    public function test_accumulates_offline_seconds(): void
    {
        $session = ExamSession::factory()->create(['offline_seconds' => 0]);

        app(RecordHeartbeat::class)($session, 'offline', 30);
        app(RecordHeartbeat::class)($session, 'offline', 20);

        $this->assertSame(50, $session->fresh()->offline_seconds);
        $this->assertSame(2, Heartbeat::query()->where('exam_session_id', $session->id)->count());
    }

    public function test_raises_connection_lost_once_the_max_offline_threshold_is_crossed(): void
    {
        Queue::fake();

        config(['aegis.exam.max_offline_seconds' => 60]);
        $session = ExamSession::factory()->create(['offline_seconds' => 0]);

        app(RecordHeartbeat::class)($session, 'offline', 70);

        Queue::assertPushed(ProcessProctoringSignal::class, fn (ProcessProctoringSignal $job) => $job->type === ViolationType::ConnectionLost);
    }

    public function test_heartbeat_endpoint_is_reachable_for_the_owning_student(): void
    {
        $exam = Exam::factory()->create();
        $session = ExamSession::factory()->create(['exam_id' => $exam->id]);

        $response = $this->actingAs($session->student)
            ->postJson(route('student.sessions.heartbeat', $session), [
                'status' => 'online',
                'duration_seconds' => 0,
            ]);

        $response->assertOk();
    }
}
