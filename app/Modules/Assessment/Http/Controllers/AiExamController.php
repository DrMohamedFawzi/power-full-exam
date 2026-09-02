<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Http\Controllers;

use App\Modules\Assessment\Actions\PersistAiQuestions;
use App\Modules\Assessment\Enums\QuestionType;
use App\Modules\Assessment\Http\Requests\GenerateAiQuestionsRequest;
use App\Modules\Assessment\Http\Requests\StoreAiQuestionsRequest;
use App\Modules\Assessment\Models\Exam;
use App\Modules\Assessment\Services\AiGenerationException;
use App\Modules\Assessment\Services\GeminiExamGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class AiExamController extends Controller
{
    public function create(Exam $exam): View
    {
        Gate::authorize('manageQuestions', $exam);

        return view('assessment.teacher.exams.ai-create', [
            'exam' => $exam,
            'types' => QuestionType::cases(),
            'configured' => filled(config('aegis.ai.api_key')) && config('aegis.ai.enabled'),
        ]);
    }

    public function generate(GenerateAiQuestionsRequest $request, Exam $exam, GeminiExamGenerator $generator): View|RedirectResponse
    {
        $types = array_map(static fn (string $t): QuestionType => QuestionType::from($t), $request->validated('types'));

        try {
            $questions = $generator->generate(
                $request->string('topic')->toString(),
                (int) $request->validated('count'),
                $request->string('difficulty')->toString(),
                $request->string('language')->toString(),
                $types,
            );
        } catch (AiGenerationException $exception) {
            return back()->withErrors(['ai' => $exception->getMessage()]);
        }

        return view('assessment.teacher.exams.ai-preview', ['exam' => $exam, 'questions' => $questions]);
    }

    public function store(StoreAiQuestionsRequest $request, Exam $exam, PersistAiQuestions $persist): JsonResponse
    {
        $count = $persist($exam, $request->validated('questions'));

        return response()->json(['count' => $count]);
    }
}
