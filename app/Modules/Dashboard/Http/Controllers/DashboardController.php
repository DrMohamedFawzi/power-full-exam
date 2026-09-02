<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dashboard\Queries\InstitutionDashboardQuery;
use App\Modules\Dashboard\Queries\StudentDashboardQuery;
use App\Modules\Dashboard\Queries\TeacherDashboardQuery;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Role landing pages. Each route is already gated by the `role:` middleware,
 * so each method knows exactly who it is serving.
 */
final class DashboardController extends Controller
{
    public function student(Request $request, StudentDashboardQuery $query): View
    {
        return view('dashboard.student', $query($request->user()));
    }

    public function teacher(Request $request, TeacherDashboardQuery $query): View
    {
        return view('dashboard.teacher', $query($request->user()));
    }

    public function institution(Request $request, InstitutionDashboardQuery $query): View
    {
        return view('dashboard.institution', $query($request->user()));
    }
}
