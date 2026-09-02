<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Http\Controllers\Institution;

use App\Modules\Assessment\Models\Exam;
use App\Modules\Assessment\Queries\ExamResultsQuery;
use App\Modules\Assessment\Queries\InstitutionExamsQuery;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** Read-only oversight: an institution browses its own classrooms' exam results. */
final class ExamResultsController extends Controller
{
    public function index(Request $request, InstitutionExamsQuery $query): View
    {
        return view('assessment.institution.exams.index', [
            'exams' => $query($request->user()->institution),
        ]);
    }

    public function show(Exam $exam, ExamResultsQuery $resultsQuery): View
    {
        Gate::authorize('view', $exam);

        return view('assessment.institution.exams.show', [
            'exam' => $exam,
            'results' => $resultsQuery($exam),
        ]);
    }
}
