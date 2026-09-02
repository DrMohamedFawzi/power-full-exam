<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Http\Controllers;

use App\Modules\Assessment\Actions\AddQuestion;
use App\Modules\Assessment\Actions\DeleteQuestion;
use App\Modules\Assessment\Actions\DuplicateQuestion;
use App\Modules\Assessment\Actions\ReorderQuestions;
use App\Modules\Assessment\Actions\UpdateQuestion;
use App\Modules\Assessment\Http\Requests\ReorderQuestionsRequest;
use App\Modules\Assessment\Http\Requests\StoreQuestionRequest;
use App\Modules\Assessment\Http\Requests\UpdateQuestionRequest;
use App\Modules\Assessment\Models\Exam;
use App\Modules\Assessment\Models\Question;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

final class QuestionController extends Controller
{
    public function store(StoreQuestionRequest $request, Exam $exam, AddQuestion $addQuestion): JsonResponse
    {
        $question = $addQuestion($exam, $request->validated());

        return response()->json(['question' => $this->present($question)], 201);
    }

    public function update(UpdateQuestionRequest $request, Exam $exam, Question $question, UpdateQuestion $updateQuestion): JsonResponse
    {
        $question = $updateQuestion($question, $request->validated());

        return response()->json(['question' => $this->present($question)]);
    }

    public function destroy(Exam $exam, Question $question, DeleteQuestion $deleteQuestion): JsonResponse
    {
        Gate::authorize('delete', $question);
        $deleteQuestion($question);

        return response()->json(status: 204);
    }

    public function duplicate(Exam $exam, Question $question, DuplicateQuestion $duplicateQuestion): JsonResponse
    {
        Gate::authorize('update', $question);
        $copy = $duplicateQuestion($question);

        return response()->json(['question' => $this->present($copy)], 201);
    }

    public function reorder(ReorderQuestionsRequest $request, Exam $exam, ReorderQuestions $reorderQuestions): JsonResponse
    {
        $reorderQuestions($exam, $request->validated('question_ids'));

        return response()->json(status: 204);
    }

    /** @return array<string, mixed> */
    private function present(Question $question): array
    {
        return [
            'id' => $question->id,
            'position' => $question->position,
            'type' => $question->type->value,
            'type_label' => $question->type->label(),
            'prompt' => $question->prompt,
            'options' => $question->options ?? [],
            'correct_answer' => $question->correct_answer ?? [],
            'explanation' => $question->explanation,
            'points' => (float) $question->points,
        ];
    }
}
