<?php

declare(strict_types=1);

namespace App\Modules\Proctoring\Data;

use App\Modules\Proctoring\Enums\ViolationType;
use App\Support\Enums\Severity;

/**
 * The output of ClassifyViolation: the type as reported, plus the severity and
 * penalty after any repetition-based escalation.
 */
final readonly class ViolationClassification
{
    public function __construct(
        public ViolationType $type,
        public Severity $severity,
        public int $penalty,
    ) {}
}
