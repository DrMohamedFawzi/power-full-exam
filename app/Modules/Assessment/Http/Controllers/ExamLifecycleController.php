<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Http\Controllers;

use App\Modules\Assessment\Actions\CloseExam;
use App\Modules\Assessment\Actions\PublishExam;
use App\Modules\Assessment\Models\Exam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

final class ExamLifecycleController extends Controller
{
    public function publish(Exam $exam, PublishExam $publishExam): RedirectResponse
    {
        Gate::authorize('publish', $exam);
        $publishExam($exam);

        return redirect()->route('teacher.exams.show', $exam)->with('success', 'تم نشر الاختبار للطلاب.');
    }

    public function close(Exam $exam, CloseExam $closeExam): RedirectResponse
    {
        Gate::authorize('close', $exam);
        $closeExam($exam);

        return redirect()->route('teacher.exams.show', $exam)->with('success', 'تم إغلاق الاختبار.');
    }
}
