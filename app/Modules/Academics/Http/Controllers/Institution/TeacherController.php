<?php

declare(strict_types=1);

namespace App\Modules\Academics\Http\Controllers\Institution;

use App\Modules\Academics\Actions\ApproveTeacher;
use App\Modules\Academics\Actions\RevokeTeacherApproval;
use App\Modules\Academics\Http\Requests\Institution\TeacherApprovalRequest;
use App\Modules\Academics\Queries\InstitutionTeachersQuery;
use App\Modules\Identity\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class TeacherController extends Controller
{
    public function index(InstitutionTeachersQuery $query): View
    {
        return view('academics.institution.teachers.index', [
            'teachers' => $query(request()->user()->institution),
        ]);
    }

    public function approve(TeacherApprovalRequest $request, User $teacher, ApproveTeacher $action): RedirectResponse
    {
        $action($teacher);

        return back()->with('success', 'تم اعتماد المعلّم.');
    }

    public function revoke(TeacherApprovalRequest $request, User $teacher, RevokeTeacherApproval $action): RedirectResponse
    {
        $action($teacher);

        return back()->with('success', 'تم إلغاء اعتماد المعلّم.');
    }
}
