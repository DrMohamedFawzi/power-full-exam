<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Http\Controllers;

use App\Modules\Assessment\Models\ExamSession;
use App\Modules\Assessment\Queries\StudentResultDetailQuery;
use App\Modules\Assessment\Queries\StudentResultsQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class StudentResultsController
{
    public function index(Request $request, StudentResultsQuery $query): View
    {
        return view('assessment.student.results.index', [
            'results' => $query($request->user()),
        ]);
    }

    public function show(Request $request, ExamSession $session, StudentResultDetailQuery $query): View
    {
        abort_unless($request->user()->can('view', $session), 403);

        return view('assessment.student.results.show', [
            'result' => $query($session),
        ]);
    }
}
