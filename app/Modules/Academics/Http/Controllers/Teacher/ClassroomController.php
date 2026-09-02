<?php

declare(strict_types=1);

namespace App\Modules\Academics\Http\Controllers\Teacher;

use App\Modules\Academics\Actions\CreateClassroom;
use App\Modules\Academics\Actions\DeleteClassroom;
use App\Modules\Academics\Actions\UpdateClassroom;
use App\Modules\Academics\Http\Requests\Teacher\DestroyClassroomRequest;
use App\Modules\Academics\Http\Requests\Teacher\StoreClassroomRequest;
use App\Modules\Academics\Http\Requests\Teacher\UpdateClassroomRequest;
use App\Modules\Academics\Models\Classroom;
use App\Modules\Academics\Queries\ClassroomDetailQuery;
use App\Modules\Academics\Queries\TeacherClassroomsQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class ClassroomController extends Controller
{
    public function index(TeacherClassroomsQuery $query): View
    {
        return view('academics.teacher.classrooms.index', [
            'classrooms' => $query(request()->user()),
        ]);
    }

    public function create(): View
    {
        return view('academics.teacher.classrooms.create');
    }

    public function store(StoreClassroomRequest $request, CreateClassroom $action): RedirectResponse
    {
        $classroom = $action($request->user(), $request->validated());

        return redirect()
            ->route('teacher.classrooms.show', $classroom)
            ->with('success', 'تم إنشاء الصف بنجاح.');
    }

    public function show(Classroom $classroom, ClassroomDetailQuery $query): View
    {
        Gate::authorize('view', $classroom);

        return view('academics.teacher.classrooms.show', $query($classroom));
    }

    public function edit(Classroom $classroom): View
    {
        Gate::authorize('update', $classroom);

        return view('academics.teacher.classrooms.edit', ['classroom' => $classroom]);
    }

    public function update(UpdateClassroomRequest $request, Classroom $classroom, UpdateClassroom $action): RedirectResponse
    {
        $action($classroom, $request->validated());

        return redirect()
            ->route('teacher.classrooms.show', $classroom)
            ->with('success', 'تم تحديث بيانات الصف.');
    }

    public function destroy(DestroyClassroomRequest $request, Classroom $classroom, DeleteClassroom $action): RedirectResponse
    {
        $deleted = $action($classroom);

        return redirect()
            ->route('teacher.classrooms.index')
            ->with('success', $deleted
                ? 'تم حذف الصف نهائيًا.'
                : 'لا يمكن حذف الصف لوجود اختبارات مرتبطة به، فتم أرشفته بدلًا من ذلك.');
    }
}
