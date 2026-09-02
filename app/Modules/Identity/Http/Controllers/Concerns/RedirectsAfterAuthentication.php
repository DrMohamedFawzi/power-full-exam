<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers\Concerns;

use App\Modules\Identity\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

/**
 * Where to send a user right after they authenticate (login, register, or QR
 * login). An unapproved teacher always lands on the holding page. Otherwise
 * their role's home route — falling back to the landing page if the other
 * module that owns that dashboard route has not been built yet.
 */
trait RedirectsAfterAuthentication
{
    private function redirectAfterAuthentication(User $user): RedirectResponse
    {
        if ($user->isTeacher() && ! $user->is_approved) {
            return redirect()->route('teacher.pending');
        }

        $homeRoute = $user->role->homeRoute();

        return redirect()->to(Route::has($homeRoute) ? route($homeRoute) : route('home'));
    }
}
