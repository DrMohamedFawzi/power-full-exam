<?php

declare(strict_types=1);

use App\Modules\Identity\Http\Controllers\Api\TokenController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Stateless API — JWT Token Endpoints
|--------------------------------------------------------------------------
|
| These routes are consumed by the exam JS client (and mobile apps in the
| future). They are intentionally separate from the session-based web
| routes in auth.php.
|
| POST   /api/auth/token          → issue token (login)
| POST   /api/auth/token/refresh  → refresh before expiry
| DELETE /api/auth/token          → invalidate (logout)
|
*/

Route::prefix('auth')->name('api.auth.')->group(function (): void {
    // Public: no middleware
    Route::post('/token', [TokenController::class, 'issue'])->name('token.issue');

    // Protected: must have a valid JWT
    Route::middleware(\App\Modules\Identity\Http\Middleware\JwtAuthMiddleware::class)->group(function (): void {
        Route::post('/token/refresh', [TokenController::class, 'refresh'])->name('token.refresh');
        Route::delete('/token', [TokenController::class, 'destroy'])->name('token.destroy');
    });
});
