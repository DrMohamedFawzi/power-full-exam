<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Stateless JWT guard for API routes.
 *
 * Replaces session-based auth on all API endpoints. The token is read from:
 *   1. Authorization: Bearer <token>  header   (preferred)
 *   2. ?token=<jwt>                   query string (fallback for WebSocket-like beacons)
 *
 * On success, sets auth()->user() so all downstream code that uses
 * $request->user() continues to work without modification.
 */
final class JwtAuthMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (! $user) {
                return $this->unauthorized('المستخدم غير موجود.');
            }

        } catch (TokenExpiredException) {
            return $this->unauthorized('انتهت صلاحية الرمز. يرجى تسجيل الدخول مجدداً.');
        } catch (TokenInvalidException) {
            return $this->unauthorized('رمز المصادقة غير صالح.');
        } catch (JWTException) {
            return $this->unauthorized('رمز المصادقة مفقود.');
        }

        return $next($request);
    }

    private function unauthorized(string $message): JsonResponse
    {
        return response()->json([
            'error'   => 'غير مصرح',
            'message' => $message,
        ], 401);
    }
}
