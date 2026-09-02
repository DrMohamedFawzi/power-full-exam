<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Modules\Identity\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * Redeems a QR login token minted by IssueQrLoginToken. The token is pulled
 * (read + delete) so it can never be replayed, and it simply expires if
 * nobody scans it in time.
 */
final class ConsumeQrLoginToken
{
    public function __invoke(Request $request, string $token): ?User
    {
        $userId = Cache::pull(IssueQrLoginToken::CACHE_PREFIX.$token);

        if ($userId === null) {
            return null;
        }

        $user = User::find($userId);

        if ($user === null || ! $user->is_approved) {
            return null;
        }

        Auth::login($user);
        $request->session()->regenerate();

        $user->forceFill([
            'last_login_ip' => $request->ip(),
            'last_login_at' => now(),
        ])->save();

        return $user;
    }
}
