<?php

declare(strict_types=1);

namespace App\Modules\Proctoring\Http\Controllers;

use App\Modules\Assessment\Models\Exam;
use App\Modules\Proctoring\Queries\ExamMonitorQuery;
use App\Modules\Proctoring\Queries\TeacherActiveExamsQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MonitorController
{
    public function index(Request $request, TeacherActiveExamsQuery $query): View
    {
        return view('proctoring.teacher.monitor.index', [
            'exams' => $query($request->user()),
        ]);
    }

    public function show(Request $request, Exam $exam): View
    {
        abort_unless($exam->created_by === $request->user()->id, 403);

        return view('proctoring.teacher.monitor.show', [
            'exam' => $exam,
        ]);
    }

    public function data(Request $request, Exam $exam, ExamMonitorQuery $query): JsonResponse
    {
        abort_unless($exam->created_by === $request->user()->id, 403);

        return response()->json(['sessions' => $query($exam)]);
    }
}
