<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers;

use App\Modules\Identity\Actions\AttemptLogin;
use App\Modules\Identity\Actions\LogoutUser;
use App\Modules\Identity\Http\Controllers\Concerns\RedirectsAfterAuthentication;
use App\Modules\Identity\Http\Requests\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

final class SessionController
{
    use RedirectsAfterAuthentication;

    public function create(): View
    {
        return view('identity.shared.login');
    }

    public function store(LoginRequest $request, AttemptLogin $attemptLogin): RedirectResponse
    {
        $attemptLogin(
            $request,
            (string) $request->string('email'),
            (string) $request->string('password'),
            $request->boolean('remember'),
        );

        return $this->redirectAfterAuthentication(Auth::user());
    }

    public function destroy(Request $request, LogoutUser $logoutUser): RedirectResponse
    {
        $logoutUser($request);

        return redirect()->route('home');
    }
}
