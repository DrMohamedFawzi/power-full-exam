<?php

declare(strict_types=1);

use App\Modules\Academics\Http\Controllers\Institution\StudentController;
use App\Modules\Academics\Http\Controllers\Institution\TeacherController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:institution'])
    ->prefix('institution')
    ->name('institution.')
    ->group(function (): void {
        Route::get('teachers', [TeacherController::class, 'index'])->name('teachers.index');
        Route::post('teachers/{teacher}/approve', [TeacherController::class, 'approve'])->name('teachers.approve');
        Route::post('teachers/{teacher}/revoke', [TeacherController::class, 'revoke'])->name('teachers.revoke');

        Route::get('students', [StudentController::class, 'index'])->name('students.index');
    });
