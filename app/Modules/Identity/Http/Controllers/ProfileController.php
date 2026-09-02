<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers;

use App\Modules\Identity\Actions\UpdateProfile;
use App\Modules\Identity\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class ProfileController
{
    public function edit(Request $request): View
    {
        return view('identity.shared.profile', [
            'user' => $request->user(),
        ]);
    }

    public function update(UpdateProfileRequest $request, UpdateProfile $updateProfile): RedirectResponse
    {
        Gate::authorize('update', $request->user());

        $updateProfile(
            $request->user(),
            (string) $request->string('official_name'),
            (string) $request->string('email'),
            $request->filled('password') ? (string) $request->string('password') : null,
            $request->file('avatar'),
        );

        return redirect()->route('profile.edit')->with('success', 'تم تحديث الملف الشخصي بنجاح.');
    }
}
