<?php

declare(strict_types=1);

namespace Tests\Unit\Proctoring;

use App\Modules\Assessment\Models\ExamSession;
use App\Modules\Proctoring\Actions\ClassifyViolation;
use App\Modules\Proctoring\Enums\ViolationType;
use App\Modules\Proctoring\Models\Violation;
use App\Support\Enums\Severity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ClassifyViolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_uses_the_enums_base_severity_for_the_first_occurrence(): void
    {
        $session = ExamSession::factory()->create();

        $classification = app(ClassifyViolation::class)($session, ViolationType::TabSwitch);

        $this->assertSame(Severity::Medium, $classification->severity);
        $this->assertSame(Severity::Medium->penalty(), $classification->penalty);
    }

    public function test_escalates_severity_after_repeated_occurrences_of_the_same_type(): void
    {
        $session = ExamSession::factory()->create();

        Violation::factory()->forSession($session)->type(ViolationType::TabSwitch)->count(3)->create();

        $classification = app(ClassifyViolation::class)($session, ViolationType::TabSwitch);

        $this->assertSame(Severity::High, $classification->severity);
    }

    public function test_escalation_caps_at_critical(): void
    {
        $session = ExamSession::factory()->create();

        Violation::factory()->forSession($session)->type(ViolationType::TabSwitch)->count(30)->create();

        $classification = app(ClassifyViolation::class)($session, ViolationType::TabSwitch);

        $this->assertSame(Severity::Critical, $classification->severity);
    }
}
