<?php

declare(strict_types=1);

use App\Modules\Overwatch\Http\Controllers\BansController;
use App\Modules\Overwatch\Http\Controllers\DashboardController;
use App\Modules\Overwatch\Http\Controllers\ThreatsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Overwatch routes
|--------------------------------------------------------------------------
| The threat console: institution-only, gated by auth + role:institution.
| ModuleServiceProvider loads this file under the `web` middleware group.
*/

Route::middleware(['auth', 'role:institution'])
    ->prefix('overwatch')
    ->name('overwatch.')
    ->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::get('/threats', [ThreatsController::class, 'index'])->name('threats.index');

        Route::get('/bans', [BansController::class, 'index'])->name('bans.index');
        Route::post('/bans', [BansController::class, 'store'])->name('bans.store');
        Route::delete('/bans/{ban}', [BansController::class, 'destroy'])->name('bans.destroy');
    });
