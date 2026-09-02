<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Mints a signed, single-use, short-TTL token for QR login. The QR image
 * never carries the user's permanent qr_token column value — only this
 * derived, cache-backed nonce, which expires and is consumed on first use.
 */
final class IssueQrLoginToken
{
    public const CACHE_PREFIX = 'identity:qr-login:';

    public function __invoke(User $user): string
    {
        $token = hash('sha256', $user->qr_token.'|'.Str::random(40));

        Cache::put(
            self::CACHE_PREFIX.$token,
            $user->id,
            now()->addSeconds((int) config('aegis.qr.ttl_seconds')),
        );

        return $token;
    }
}
