<?php

declare(strict_types=1);

namespace App\Modules\Academics\Http\Controllers\Student;

use App\Modules\Academics\Actions\SubmitEnrollmentRequest;
use App\Modules\Academics\Http\Requests\Student\JoinClassroomRequest;
use App\Modules\Academics\Queries\StudentClassroomsQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class ClassroomController extends Controller
{
    public function index(StudentClassroomsQuery $query): View
    {
        return view('academics.student.classrooms.index', $query(request()->user()));
    }

    public function join(JoinClassroomRequest $request, SubmitEnrollmentRequest $action): RedirectResponse
    {
        $action($request->user(), $request->validated('code'));

        return back()->with('success', 'تم إرسال طلب الانضمام، بانتظار موافقة المعلّم.');
    }
}
