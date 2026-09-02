<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Http\Controllers;

use App\Modules\Assessment\Models\Exam;
use App\Modules\Assessment\Models\ExamSession;
use App\Modules\Assessment\Queries\StudentAvailableExamsQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class StudentExamController
{
    public function index(Request $request, StudentAvailableExamsQuery $query): View
    {
        return view('assessment.student.exams.index', [
            'exams' => $query($request->user()),
        ]);
    }

    public function show(Request $request, Exam $exam): View
    {
        abort_unless($request->user()->can('create', [ExamSession::class, $exam]), 404);

        return view('assessment.student.exams.show', [
            'exam' => $exam,
        ]);
    }
}
