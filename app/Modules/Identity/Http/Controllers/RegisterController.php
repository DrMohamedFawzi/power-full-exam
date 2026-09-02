<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers;

use App\Modules\Identity\Actions\RegisterUser;
use App\Modules\Identity\Data\RegistrationData;
use App\Modules\Identity\Http\Controllers\Concerns\RedirectsAfterAuthentication;
use App\Modules\Identity\Http\Requests\RegisterRequest;
use App\Modules\Identity\Queries\InstitutionOptionsQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

final class RegisterController
{
    use RedirectsAfterAuthentication;

    public function create(InstitutionOptionsQuery $institutions): View
    {
        return view('identity.shared.register', [
            'institutions' => $institutions(),
        ]);
    }

    public function store(RegisterRequest $request, RegisterUser $registerUser): RedirectResponse
    {
        $user = $registerUser(RegistrationData::fromValidated($request->validated()));

        Auth::login($user);
        $request->session()->regenerate();

        return $this->redirectAfterAuthentication($user)->with('success', 'تم إنشاء الحساب بنجاح.');
    }
}
