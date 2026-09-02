<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

final class HomeController
{
    /**
     * Authenticated visitors are sent to their dashboard when it exists; an
     * unapproved teacher goes to the holding page. Otherwise (guests, or a
     * role whose dashboard module is not built yet) they see the landing page.
     */
    public function __invoke(): View|RedirectResponse
    {
        $user = Auth::user();

        if ($user === null) {
            return view('identity.home');
        }

        if ($user->isTeacher() && ! $user->is_approved) {
            return redirect()->route('teacher.pending');
        }

        $homeRoute = $user->role->homeRoute();

        return Route::has($homeRoute) ? redirect()->route($homeRoute) : view('identity.home');
    }
}
