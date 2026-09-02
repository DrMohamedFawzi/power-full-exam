<?php

declare(strict_types=1);

use App\Modules\Assessment\Http\Controllers\SessionController;
use App\Modules\Assessment\Http\Controllers\StudentExamController;
use App\Modules\Assessment\Http\Controllers\StudentResultsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Assessment — student runtime routes
|--------------------------------------------------------------------------
| Exam authoring lives in Routes/authoring.php, owned by a different agent.
| Every path here contains "exam", which Overwatch's WAF body-scan exempts —
| see ShieldRequest.
*/

Route::middleware(['auth', 'role:student', \App\Http\Middleware\EnsureStudentFaceApproved::class])->prefix('exams')->name('student.exams.')->group(function (): void {
    Route::get('/', [StudentExamController::class, 'index'])->name('index');
    Route::get('/{exam}', [StudentExamController::class, 'show'])->name('show');
});

Route::middleware(['auth', 'role:student', \App\Http\Middleware\EnsureStudentFaceApproved::class])->prefix('exam-sessions')->name('student.sessions.')->group(function (): void {
    Route::post('/{exam}', [SessionController::class, 'store'])->name('store');
    Route::get('/{session}', [SessionController::class, 'show'])->name('show');
    Route::post('/{session}/answer', [SessionController::class, 'answer'])->name('answer');
    Route::post('/{session}/submit', [SessionController::class, 'submit'])->name('submit');
});

Route::middleware(['auth', 'role:student'])->prefix('exam-results')->name('student.results.')->group(function (): void {
    Route::get('/', [StudentResultsController::class, 'index'])->name('index');
    Route::get('/{session}', [StudentResultsController::class, 'show'])->name('show');
});
