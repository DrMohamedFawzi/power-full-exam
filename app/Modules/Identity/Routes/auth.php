<?php

declare(strict_types=1);

use App\Modules\Identity\Http\Controllers\DeviceController;
use App\Modules\Identity\Http\Controllers\ProfileController;
use App\Modules\Identity\Http\Controllers\QrLoginController;
use App\Modules\Identity\Http\Controllers\SessionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authenticated surface
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [SessionController::class, 'destroy'])->name('logout');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/teacher/pending', fn () => view('identity.teacher.pending'))
        ->middleware('role:teacher')
        ->name('teacher.pending');

    Route::post('/admin/students/{user}/approve-face', [\App\Modules\Identity\Http\Controllers\FaceProfileController::class, 'approve'])->name('admin.students.approve-face');
    Route::post('/admin/students/{user}/reject-face', [\App\Modules\Identity\Http\Controllers\FaceProfileController::class, 'reject'])->name('admin.students.reject-face');

    Route::middleware('role:student')->group(function (): void {
        Route::get('/student/profile/face', [\App\Modules\Identity\Http\Controllers\FaceProfileController::class, 'show'])->name('student.profile.face');
        Route::post('/student/profile/face', [\App\Modules\Identity\Http\Controllers\FaceProfileController::class, 'storeDescriptor'])->name('student.profile.face.store');

        Route::get('/qr-login/show', [QrLoginController::class, 'show'])->name('qr.show');

        Route::get('/student/devices', [DeviceController::class, 'index'])->name('student.devices.index');
        Route::delete('/student/devices/{device}', [DeviceController::class, 'destroy'])->name('student.devices.destroy');
    });
});
