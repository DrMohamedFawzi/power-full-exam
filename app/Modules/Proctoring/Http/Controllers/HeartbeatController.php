<?php

declare(strict_types=1);

namespace App\Modules\Proctoring\Http\Controllers;

use App\Modules\Assessment\Models\ExamSession;
use App\Modules\Proctoring\Actions\RecordHeartbeat;
use App\Modules\Proctoring\Http\Requests\StoreHeartbeatRequest;
use Illuminate\Http\JsonResponse;

final class HeartbeatController
{
    public function store(StoreHeartbeatRequest $request, ExamSession $session, RecordHeartbeat $action): JsonResponse
    {
        $action(
            $session,
            (string) $request->string('status'),
            (int) $request->integer('duration_seconds'),
            $request->filled('client_time') ? (int) $request->integer('client_time') : null,
        );

        return response()->json([
            'offline_seconds' => $session->fresh()->offline_seconds,
            'integrity_index' => $session->fresh()->integrity_index,
        ]);
    }
}
