<?php

declare(strict_types=1);

namespace App\Modules\Academics\Http\Controllers\Institution;

use App\Modules\Academics\Queries\InstitutionStudentsQuery;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class StudentController extends Controller
{
    public function index(Request $request, InstitutionStudentsQuery $query): View
    {
        return view('academics.institution.students.index', [
            'students' => $query($request->user()->institution, $request->string('search')->toString() ?: null),
        ]);
    }
}
