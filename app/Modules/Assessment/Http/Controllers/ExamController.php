<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Http\Controllers;

use App\Modules\Assessment\Actions\CreateExam;
use App\Modules\Assessment\Actions\DeleteExam;
use App\Modules\Assessment\Actions\UpdateExam;
use App\Modules\Assessment\Enums\ExamMode;
use App\Modules\Assessment\Enums\SecurityLevel;
use App\Modules\Assessment\Http\Requests\StoreExamRequest;
use App\Modules\Assessment\Http\Requests\UpdateExamRequest;
use App\Modules\Assessment\Models\Exam;
use App\Modules\Assessment\Queries\ExamBuilderQuery;
use App\Modules\Assessment\Queries\ExamOverviewQuery;
use App\Modules\Assessment\Queries\TeacherClassroomOptionsQuery;
use App\Modules\Assessment\Queries\TeacherExamsQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class ExamController extends Controller
{
    public function index(TeacherExamsQuery $query): View
    {
        return view('assessment.teacher.exams.index', ['exams' => $query(request()->user())]);
    }

    public function create(TeacherClassroomOptionsQuery $classrooms): View
    {
        Gate::authorize('create', Exam::class);

        return view('assessment.teacher.exams.create', [
            'classrooms' => $classrooms(request()->user()),
            'securityLevels' => SecurityLevel::cases(),
            'modes' => ExamMode::cases(),
        ]);
    }

    public function store(StoreExamRequest $request, CreateExam $createExam): RedirectResponse
    {
        $exam = $createExam(request()->user(), $request->validatedData());

        return redirect()->route('teacher.exams.edit', $exam)->with('success', 'تم إنشاء الاختبار كمسودة بنجاح.');
    }

    public function show(Exam $exam, ExamOverviewQuery $query): View
    {
        Gate::authorize('view', $exam);

        return view('assessment.teacher.exams.show', ['exam' => $query($exam)]);
    }

    public function edit(Exam $exam, ExamBuilderQuery $query, TeacherClassroomOptionsQuery $classrooms): View
    {
        Gate::authorize('update', $exam);

        return view('assessment.teacher.exams.edit', [
            'exam' => $exam,
            'data' => $query($exam),
            'classrooms' => $classrooms(request()->user()),
            'securityLevels' => SecurityLevel::cases(),
            'modes' => ExamMode::cases(),
        ]);
    }

    public function update(UpdateExamRequest $request, Exam $exam, UpdateExam $updateExam): RedirectResponse
    {
        $updateExam($exam, $request->validatedData());

        return redirect()->route('teacher.exams.edit', $exam)->with('success', 'تم تحديث بيانات الاختبار.');
    }

    public function destroy(Exam $exam, DeleteExam $deleteExam): RedirectResponse
    {
        Gate::authorize('delete', $exam);
        $deleteExam($exam);

        return redirect()->route('teacher.exams.index')->with('success', 'تم حذف الاختبار.');
    }
}
