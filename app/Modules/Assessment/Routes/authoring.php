<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Assessment — Authoring routes (teacher-facing)
|--------------------------------------------------------------------------
| Exam CRUD, question builder, publish/close lifecycle, AI generation and
| results/grading. The runtime agent owns Routes/runtime.php separately.
*/

use App\Modules\Assessment\Http\Controllers\AiExamController;
use App\Modules\Assessment\Http\Controllers\ExamController;
use App\Modules\Assessment\Http\Controllers\ExamLifecycleController;
use App\Modules\Assessment\Http\Controllers\GradingController;
use App\Modules\Assessment\Http\Controllers\QuestionController;
use App\Modules\Assessment\Http\Controllers\TeacherResultsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:teacher', 'approved'])
    ->prefix('teacher')
    ->name('teacher.')
    ->group(function (): void {
        Route::resource('exams', ExamController::class);

        Route::post('exams/{exam}/publish', [ExamLifecycleController::class, 'publish'])->name('exams.publish');
        Route::post('exams/{exam}/close', [ExamLifecycleController::class, 'close'])->name('exams.close');

        Route::post('exams/{exam}/questions', [QuestionController::class, 'store'])->name('exams.questions.store');
        Route::put('exams/{exam}/questions/reorder', [QuestionController::class, 'reorder'])->name('exams.questions.reorder');
        Route::put('exams/{exam}/questions/{question}', [QuestionController::class, 'update'])->name('exams.questions.update');
        Route::delete('exams/{exam}/questions/{question}', [QuestionController::class, 'destroy'])->name('exams.questions.destroy');
        Route::post('exams/{exam}/questions/{question}/duplicate', [QuestionController::class, 'duplicate'])->name('exams.questions.duplicate');

        Route::get('exams/{exam}/ai', [AiExamController::class, 'create'])->name('exams.ai.create');
        Route::post('exams/{exam}/ai/generate', [AiExamController::class, 'generate'])->name('exams.ai.generate');
        Route::post('exams/{exam}/ai/store', [AiExamController::class, 'store'])->name('exams.ai.store');

        Route::get('results', [TeacherResultsController::class, 'index'])->name('results.index');
        Route::get('results/{exam}', [TeacherResultsController::class, 'show'])->name('results.show');

        Route::patch('answers/{answer}/grade', [GradingController::class, 'update'])->name('answers.grade');
    });
