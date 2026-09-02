<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Issues and refreshes JWT tokens for the stateless API layer.
 *
 * Routes:
 *   POST /api/auth/token           → issue new token (login)
 *   POST /api/auth/token/refresh   → refresh expiring token
 *   DELETE /api/auth/token         → invalidate token (logout)
 */
final class TokenController
{
    /**
     * Issue a JWT token.
     * Exam clients call this once at exam start and store the token in
     * memory (never localStorage) for subsequent API calls.
     */
    public function issue(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $authenticated = Auth::guard('web')->attempt($credentials);

        if (! $authenticated) {
            throw ValidationException::withMessages([
                'username' => ['بيانات الاعتماد غير صحيحة.'],
            ]);
        }

        /** @var \Illuminate\Contracts\Auth\Authenticatable $user */
        $user = Auth::guard('web')->user();
        $token = class_exists(JWTAuth::class) ? (string) JWTAuth::fromUser($user) : base64_encode(json_encode(['id' => $user->getAuthIdentifier()]));

        return $this->tokenResponse($token);
    }

    /**
     * Refresh an expiring token without re-authentication.
     * Called automatically by the exam JS client 5 minutes before expiry.
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            $newToken = JWTAuth::parseToken()->refresh();
            return $this->tokenResponse($newToken);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['error' => 'لا يمكن تجديد الرمز: '.$e->getMessage()], 401);
        }
    }

    /**
     * Invalidate the token (logout).
     */
    public function destroy(): JsonResponse
    {
        try {
            JWTAuth::parseToken()->invalidate();
        } catch (\Throwable) {
            // Token already expired or missing — treat as logged out.
        }

        return response()->json(['message' => 'تم تسجيل الخروج بنجاح.']);
    }

    /** @return array<string, mixed> */
    private function tokenResponse(string $token): JsonResponse
    {
        $ttl = (int) config('jwt.ttl');

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => $ttl * 60, // seconds
        ]);
    }
}
