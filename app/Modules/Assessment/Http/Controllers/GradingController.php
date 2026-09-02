<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Http\Controllers;

use App\Modules\Assessment\Actions\GradeAnswer;
use App\Modules\Assessment\Http\Requests\GradeAnswerRequest;
use App\Modules\Assessment\Models\ExamAnswer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;

final class GradingController extends Controller
{
    public function update(GradeAnswerRequest $request, ExamAnswer $answer, GradeAnswer $gradeAnswer): RedirectResponse
    {
        $gradeAnswer($answer, (float) $request->validated('points_awarded'));

        return back()->with('success', 'تم تحديث درجة الإجابة.');
    }
}
