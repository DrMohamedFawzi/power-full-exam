<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Http\Controllers;

use App\Modules\Assessment\Actions\ExpireExamSession;
use App\Modules\Assessment\Actions\SaveAnswer;
use App\Modules\Assessment\Actions\StartExamSession;
use App\Modules\Assessment\Actions\SubmitExamSession;
use App\Modules\Assessment\Http\Requests\SaveAnswerRequest;
use App\Modules\Assessment\Http\Requests\StartSessionRequest;
use App\Modules\Assessment\Http\Requests\SubmitSessionRequest;
use App\Modules\Assessment\Models\ExamSession;
use App\Modules\Assessment\Queries\ExamRunnerQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class SessionController
{
    public function store(StartSessionRequest $request, StartExamSession $action): RedirectResponse
    {
        $session = $action(
            $request->user(),
            $request->exam(),
            $request->string('device_hash')->value() ?: null,
            $request->fingerprint(),
            (string) $request->ip(),
        );

        return redirect()->route('student.sessions.show', $session);
    }

    public function show(Request $request, ExamSession $session, ExamRunnerQuery $query, ExpireExamSession $expire): View|RedirectResponse
    {
        abort_unless($request->user()->can('view', $session), 403);

        $session = $expire->ifDue($session);

        if ($session->status->isFinished()) {
            return redirect()->route('student.results.show', $session)
                ->with('error', 'تم إنهاء أو تسليم هذا الاختبار ولا يمكن الدخول إليه مرة أخرى.');
        }

        return view('assessment.student.sessions.show', [
            'runner' => $query($session),
        ]);
    }

    public function answer(SaveAnswerRequest $request, ExamSession $session, SaveAnswer $action): JsonResponse
    {
        $action(
            $session,
            (int) $request->integer('question_id'),
            (array) $request->input('answer', []),
            (int) $request->integer('time_spent_seconds'),
        );

        return response()->json(['saved' => true]);
    }

    public function submit(SubmitSessionRequest $request, ExamSession $session, SubmitExamSession $action): RedirectResponse
    {
        $action($session);

        return redirect()->route('student.results.show', $session);
    }
}
