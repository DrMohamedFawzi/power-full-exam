<?php

declare(strict_types=1);

namespace App\Modules\Academics\Http\Controllers\Teacher;

use App\Modules\Academics\Actions\ApproveEnrollmentRequest;
use App\Modules\Academics\Actions\BulkApproveEnrollmentRequests;
use App\Modules\Academics\Actions\RejectEnrollmentRequest as RejectEnrollmentRequestAction;
use App\Modules\Academics\Http\Requests\Teacher\ApproveEnrollmentFormRequest;
use App\Modules\Academics\Http\Requests\Teacher\BulkApproveEnrollmentsRequest;
use App\Modules\Academics\Http\Requests\Teacher\RejectEnrollmentFormRequest;
use App\Modules\Academics\Models\Classroom;
use App\Modules\Academics\Models\EnrollmentRequest;
use App\Modules\Academics\Queries\TeacherEnrollmentsQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class EnrollmentController extends Controller
{
    public function index(TeacherEnrollmentsQuery $query): View
    {
        return view('academics.teacher.enrollments.index', [
            'enrollments' => $query(request()->user()),
        ]);
    }

    public function approve(
        ApproveEnrollmentFormRequest $request,
        EnrollmentRequest $enrollment,
        ApproveEnrollmentRequest $action,
    ): RedirectResponse {
        $action($enrollment, $request->user());

        return back()->with('success', 'تم قبول طلب الانضمام.');
    }

    public function reject(
        RejectEnrollmentFormRequest $request,
        EnrollmentRequest $enrollment,
        RejectEnrollmentRequestAction $action,
    ): RedirectResponse {
        $action($enrollment, $request->user(), $request->validated('reason'));

        return back()->with('success', 'تم رفض طلب الانضمام.');
    }

    public function bulkApprove(
        BulkApproveEnrollmentsRequest $request,
        Classroom $classroom,
        BulkApproveEnrollmentRequests $action,
    ): RedirectResponse {
        $count = $action($classroom, $request->user(), $request->validated('request_ids', []));

        return back()->with('success', "تم قبول {$count} طلب انضمام.");
    }
}
