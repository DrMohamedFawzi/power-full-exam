<?php

declare(strict_types=1);

use App\Modules\Academics\Http\Controllers\Teacher\ClassroomController;
use App\Modules\Academics\Http\Controllers\Teacher\EnrollmentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:teacher', 'approved'])
    ->prefix('teacher')
    ->name('teacher.')
    ->group(function (): void {
        Route::resource('classrooms', ClassroomController::class);

        Route::get('enrollments', [EnrollmentController::class, 'index'])->name('enrollments.index');
        Route::post('enrollments/{enrollment}/approve', [EnrollmentController::class, 'approve'])->name('enrollments.approve');
        Route::post('enrollments/{enrollment}/reject', [EnrollmentController::class, 'reject'])->name('enrollments.reject');
        Route::post('classrooms/{classroom}/enrollments/bulk-approve', [EnrollmentController::class, 'bulkApprove'])
            ->name('enrollments.bulk-approve');
    });
