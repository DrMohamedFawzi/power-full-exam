<?php

declare(strict_types=1);

namespace App\Modules\Proctoring\Jobs;

use App\Modules\Assessment\Enums\SessionStatus;
use App\Modules\Assessment\Models\ExamSession;
use App\Modules\Proctoring\Actions\AnalyzeKeystrokeDynamics;
use App\Modules\Proctoring\Actions\ClassifyViolation;
use App\Modules\Proctoring\Actions\RecalculateIntegrityIndex;
use App\Modules\Proctoring\Actions\RecordViolation;
use App\Modules\Proctoring\Enums\ViolationType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * The queue is the whole point: the ingest endpoint returns instantly and this
 * job does the actual classification/scoring off the request cycle. For a
 * keystroke-anomaly signal it runs the timing analysis first and only records
 * a violation when the analysis itself flags an anomaly — every other window
 * is baseline data, not a violation.
 */
final class ProcessProctoringSignal implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** @param  array<string, mixed>  $metadata */
    public function __construct(
        public readonly ExamSession $session,
        public readonly ViolationType $type,
        public readonly ?string $details = null,
        public readonly array $metadata = [],
    ) {
        $this->onQueue('proctoring');
    }

    public function handle(
        AnalyzeKeystrokeDynamics $analyze,
        ClassifyViolation $classify,
        RecordViolation $record,
        RecalculateIntegrityIndex $recalculate,
    ): void {
        $session = $this->session->fresh();

        if ($session === null || $session->status !== SessionStatus::Active) {
            return;
        }

        $metadata = $this->metadata;

        if ($this->type === ViolationType::KeystrokeAnomaly && isset($metadata['intervals'])) {
            $result = $analyze($session, (array) $metadata['intervals']);

            if (! $result->isAnomalous) {
                return;
            }

            $metadata['anomaly_score'] = (float) $result->sample->anomaly_score;
            unset($metadata['intervals']);
        }

        $classification = $classify($session, $this->type);
        $record($session, $classification, $this->details, $metadata === [] ? null : $metadata);
        $recalculate($session);
    }
}
