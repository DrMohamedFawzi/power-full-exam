<?php

declare(strict_types=1);

use App\Modules\Identity\Http\Controllers\HomeController;
use App\Modules\Identity\Http\Controllers\QrLoginController;
use App\Modules\Identity\Http\Controllers\RegisterController;
use App\Modules\Identity\Http\Controllers\SessionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public / guest surface
|--------------------------------------------------------------------------
| The landing page, registration, password login, and the QR-scan endpoint
| (reached from another device that is not yet authenticated).
*/

Route::get('/', HomeController::class)->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);

    Route::get('/login', [SessionController::class, 'create'])->name('login');
    Route::post('/login', [SessionController::class, 'store']);
});

// Reached by scanning the QR code shown on qr.show — the device doing the
// scanning is not authenticated yet, so this stays outside the auth group.
Route::get('/qr-login', [QrLoginController::class, 'login'])->name('qr.login');

/*
|--------------------------------------------------------------------------
| System Sandbox & Live Test (Interactive Onboarding & Evaluation)
|--------------------------------------------------------------------------
*/
use App\Modules\Identity\Http\Controllers\SandboxController;

Route::prefix('sandbox')->name('sandbox.')->group(function (): void {
    Route::get('/student', [SandboxController::class, 'studentGateway'])->name('student.gateway');
    Route::get('/student/exam', [SandboxController::class, 'studentExam'])->name('student.exam');
    Route::get('/teacher', [SandboxController::class, 'teacherDemo'])->name('teacher');
    Route::get('/institution', [SandboxController::class, 'institutionDemo'])->name('institution');
    Route::get('/family', [SandboxController::class, 'familySurvey'])->name('family');
    Route::get('/surveys', [SandboxController::class, 'surveysDashboard'])->name('surveys');
    Route::post('/surveys', [SandboxController::class, 'storeSurvey'])->name('surveys.store');
    Route::get('/surveys/export', [SandboxController::class, 'exportReport'])->name('surveys.export');
});
