<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Assessment — Institution routes (read-only oversight)
|--------------------------------------------------------------------------
| An institution never authors or grades exams; it only browses results
| across its own classrooms. Authoring lives in Routes/authoring.php.
*/

use App\Modules\Assessment\Http\Controllers\Institution\ExamResultsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:institution'])
    ->prefix('institution')
    ->name('institution.')
    ->group(function (): void {
        Route::get('exams', [ExamResultsController::class, 'index'])->name('exams.index');
        Route::get('exams/{exam}', [ExamResultsController::class, 'show'])->name('exams.show');
    });
