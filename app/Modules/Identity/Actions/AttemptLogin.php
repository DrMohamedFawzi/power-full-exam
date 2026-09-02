<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Session login: rate-limited 5/min per email+IP, records the login IP/time,
 * and regenerates the session on success.
 */
final class AttemptLogin
{
    private const MAX_ATTEMPTS = 5;

    public function __invoke(Request $request, string $email, string $password, bool $remember = false): void
    {
        $key = $this->throttleKey($request, $email);

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'email' => 'محاولات دخول كثيرة جداً. يرجى المحاولة بعد '.RateLimiter::availableIn($key).' ثانية.',
            ]);
        }

        if (! Auth::attempt(['email' => $email, 'password' => $password], $remember)) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'email' => 'بيانات الدخول غير صحيحة.',
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        $user = $request->user();
        $user?->forceFill([
            'last_login_ip' => $request->ip(),
            'last_login_at' => now(),
        ])->save();
    }

    private function throttleKey(Request $request, string $email): string
    {
        return mb_strtolower($email).'|'.$request->ip();
    }
}
