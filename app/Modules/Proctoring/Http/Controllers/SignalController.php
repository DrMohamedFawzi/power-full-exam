<?php

declare(strict_types=1);

namespace App\Modules\Proctoring\Http\Controllers;

use App\Modules\Assessment\Enums\SessionStatus;
use App\Modules\Assessment\Models\ExamSession;
use App\Modules\Proctoring\Http\Requests\StoreSignalRequest;
use App\Modules\Proctoring\Jobs\ProcessProctoringSignal;
use Illuminate\Http\JsonResponse;

/**
 * Never blocks the student: validates shape, queues one job per signal, and
 * returns immediately. All classification/scoring happens off-request in
 * ProcessProctoringSignal.
 */
final class SignalController
{
    public function store(StoreSignalRequest $request, ExamSession $session): JsonResponse
    {
        if ($session->status === SessionStatus::Active) {
            foreach ($request->signals() as $signal) {
                ProcessProctoringSignal::dispatch($session, $signal['type'], $signal['details'], $signal['metadata']);
            }
        }

        return response()->json(['accepted' => true], 202);
    }
}
