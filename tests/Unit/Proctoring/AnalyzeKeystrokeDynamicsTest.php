<?php

declare(strict_types=1);

namespace Tests\Unit\Proctoring;

use App\Modules\Assessment\Models\ExamSession;
use App\Modules\Proctoring\Actions\AnalyzeKeystrokeDynamics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AnalyzeKeystrokeDynamicsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_first_sample_establishes_a_baseline_and_is_never_anomalous(): void
    {
        $session = ExamSession::factory()->create();

        $result = app(AnalyzeKeystrokeDynamics::class)($session, [150, 160, 140, 155, 148]);

        $this->assertFalse($result->isAnomalous);
        $this->assertDatabaseHas('keystroke_samples', ['exam_session_id' => $session->id, 'sample_size' => 5]);
    }

    public function test_flags_a_sharp_deviation_from_the_sessions_baseline(): void
    {
        $session = ExamSession::factory()->create();
        $analyze = app(AnalyzeKeystrokeDynamics::class);

        // Establish a tight, consistent baseline around ~150ms.
        for ($i = 0; $i < 3; $i++) {
            $analyze($session, [150, 152, 148, 151, 149]);
        }

        // A wildly different rhythm — much faster, as if someone else took over.
        $result = $analyze($session, [20, 22, 18, 21, 19]);

        $this->assertTrue($result->isAnomalous);
    }

    public function test_too_few_keystrokes_never_triggers_an_anomaly(): void
    {
        $session = ExamSession::factory()->create();
        $analyze = app(AnalyzeKeystrokeDynamics::class);

        for ($i = 0; $i < 3; $i++) {
            $analyze($session, [150, 152, 148, 151, 149]);
        }

        $result = $analyze($session, [20, 18]);

        $this->assertFalse($result->isAnomalous);
    }
}
