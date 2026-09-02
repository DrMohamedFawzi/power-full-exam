<?php

declare(strict_types=1);

use App\Modules\Academics\Http\Controllers\Student\ClassroomController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:student'])
    ->prefix('student')
    ->name('student.')
    ->group(function (): void {
        Route::get('classrooms', [ClassroomController::class, 'index'])->name('classrooms.index');
        Route::post('classrooms/join', [ClassroomController::class, 'join'])->name('classrooms.join');
    });
