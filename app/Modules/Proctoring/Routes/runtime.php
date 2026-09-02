<?php

declare(strict_types=1);

use App\Modules\Proctoring\Http\Controllers\HeartbeatController;
use App\Modules\Proctoring\Http\Controllers\MonitorController;
use App\Modules\Proctoring\Http\Controllers\SignalController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Proctoring — signal ingest + teacher live monitor
|--------------------------------------------------------------------------
| Every path contains "exam", which Overwatch's WAF body-scan exempts — see
| ShieldRequest. Signal/heartbeat ingest never blocks: the controllers only
| validate shape and queue a job.
*/

Route::middleware(['auth', 'role:student'])->prefix('exam-sessions')->name('student.sessions.')->group(function (): void {
    Route::post('/{session}/signal', [SignalController::class, 'store'])->name('signal');
    Route::post('/{session}/heartbeat', [HeartbeatController::class, 'store'])->name('heartbeat');
});

Route::middleware(['auth', 'role:teacher', 'approved'])->prefix('exam-monitor')->name('teacher.monitor.')->group(function (): void {
    Route::get('/', [MonitorController::class, 'index'])->name('index');
    Route::get('/{exam}', [MonitorController::class, 'show'])->name('show');
    Route::get('/{exam}/data', [MonitorController::class, 'data'])->name('data');
});
