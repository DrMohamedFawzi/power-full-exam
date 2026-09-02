<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Modules\Identity\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Updates a user's own profile: name, email, optional password change, and an
 * optional avatar replacement (old file removed once the new one is stored).
 */
final class UpdateProfile
{
    public function __invoke(
        User $user,
        string $officialName,
        string $email,
        ?string $password,
        ?UploadedFile $avatar,
    ): User {
        $user->official_name = $officialName;
        $user->email = $email;

        if ($password !== null && $password !== '') {
            $user->password = Hash::make($password);
        }

        if ($avatar !== null) {
            $this->replaceAvatar($user, $avatar);
        }

        $user->save();

        return $user;
    }

    private function replaceAvatar(User $user, UploadedFile $avatar): void
    {
        $oldPath = $user->avatar_path;

        $user->avatar_path = $avatar->store('avatars', 'public');

        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }
    }
}
