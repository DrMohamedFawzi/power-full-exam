<?php

declare(strict_types=1);

namespace Tests\Unit\Proctoring;

use App\Modules\Assessment\Enums\SecurityLevel;
use App\Modules\Assessment\Enums\SessionStatus;
use App\Modules\Assessment\Models\Exam;
use App\Modules\Assessment\Models\ExamSession;
use App\Modules\Proctoring\Actions\RecalculateIntegrityIndex;
use App\Modules\Proctoring\Enums\ViolationType;
use App\Modules\Proctoring\Models\Violation;
use App\Support\Enums\Severity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RecalculateIntegrityIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_recomputes_the_index_from_the_full_violation_set(): void
    {
        $exam = Exam::factory()->security(SecurityLevel::Moderate)->create();
        $session = ExamSession::factory()->create(['exam_id' => $exam->id, 'integrity_index' => 100]);

        Violation::factory()->forSession($session)->type(ViolationType::WindowBlur)->create();
        Violation::factory()->forSession($session)->type(ViolationType::TabSwitch)->create();

        $index = app(RecalculateIntegrityIndex::class)($session);

        $expectedPenalty = Severity::Low->penalty() + Severity::Medium->penalty();
        $this->assertSame(100 - $expectedPenalty, $index);
        $this->assertSame($index, $session->fresh()->integrity_index);
    }

    public function test_the_index_is_deterministic_regardless_of_recomputation_order(): void
    {
        $exam = Exam::factory()->security(SecurityLevel::Moderate)->create();
        $session = ExamSession::factory()->create(['exam_id' => $exam->id]);

        Violation::factory()->forSession($session)->type(ViolationType::CopyAttempt)->create();

        $first = app(RecalculateIntegrityIndex::class)($session);
        $second = app(RecalculateIntegrityIndex::class)($session->fresh());

        $this->assertSame($first, $second);
    }

    public function test_floors_at_zero(): void
    {
        $exam = Exam::factory()->security(SecurityLevel::Moderate)->create();
        $session = ExamSession::factory()->create(['exam_id' => $exam->id]);

        Violation::factory()->forSession($session)->severity(Severity::Critical)->count(10)->create();

        $index = app(RecalculateIntegrityIndex::class)($session);

        $this->assertSame(0, $index);
    }

    public function test_terminates_a_strict_session_below_the_auto_submit_threshold(): void
    {
        config(['aegis.proctoring.auto_submit_threshold' => 50]);

        $exam = Exam::factory()->security(SecurityLevel::Strict)->create();
        $session = ExamSession::factory()->create(['exam_id' => $exam->id]);

        Violation::factory()->forSession($session)->severity(Severity::Critical)->count(3)->create();

        app(RecalculateIntegrityIndex::class)($session);

        $this->assertSame(SessionStatus::Terminated, $session->fresh()->status);
    }

    public function test_does_not_terminate_a_moderate_session_below_the_threshold(): void
    {
        config(['aegis.proctoring.auto_submit_threshold' => 50]);

        $exam = Exam::factory()->security(SecurityLevel::Moderate)->create();
        $session = ExamSession::factory()->create(['exam_id' => $exam->id]);

        Violation::factory()->forSession($session)->severity(Severity::Critical)->count(3)->create();

        app(RecalculateIntegrityIndex::class)($session);

        $this->assertSame(SessionStatus::Active, $session->fresh()->status);
    }
}
