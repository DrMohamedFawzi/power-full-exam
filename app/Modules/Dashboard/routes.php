<?php

declare(strict_types=1);

use App\Modules\Dashboard\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function (): void {
    Route::get('/student/dashboard', [DashboardController::class, 'student'])
        ->middleware('role:student')
        ->name('student.dashboard');

    Route::get('/teacher/dashboard', [DashboardController::class, 'teacher'])
        ->middleware(['role:teacher', 'approved'])
        ->name('teacher.dashboard');

    Route::get('/institution/dashboard', [DashboardController::class, 'institution'])
        ->middleware('role:institution')
        ->name('institution.dashboard');
});
