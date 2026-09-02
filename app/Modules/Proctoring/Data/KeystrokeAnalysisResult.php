<?php

declare(strict_types=1);

namespace App\Modules\Proctoring\Data;

use App\Modules\Proctoring\Models\KeystrokeSample;

/**
 * The output of AnalyzeKeystrokeDynamics: the persisted sample plus whether it
 * deviates enough from the session's baseline to be reported as a violation.
 */
final readonly class KeystrokeAnalysisResult
{
    public function __construct(
        public KeystrokeSample $sample,
        public bool $isAnomalous,
    ) {}
}
