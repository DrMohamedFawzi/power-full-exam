<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Http\Controllers;

use App\Modules\Assessment\Models\Exam;
use App\Modules\Assessment\Queries\ExamResultsQuery;
use App\Modules\Assessment\Queries\PendingGradingQuery;
use App\Modules\Assessment\Queries\TeacherResultsIndexQuery;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class TeacherResultsController extends Controller
{
    public function index(TeacherResultsIndexQuery $query): View
    {
        return view('assessment.teacher.results.index', ['exams' => $query(request()->user())]);
    }

    public function show(Exam $exam, ExamResultsQuery $resultsQuery, PendingGradingQuery $gradingQuery): View
    {
        Gate::authorize('view', $exam);

        return view('assessment.teacher.results.show', [
            'exam' => $exam,
            'results' => $resultsQuery($exam),
            'pendingGrading' => $gradingQuery($exam),
        ]);
    }
}
