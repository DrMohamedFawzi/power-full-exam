<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentFaceApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isStudent() && ! $user->isPhotoApproved()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'يجب اعتماد الصورة الشخصية والبصمة الرقمية للوجه قبل دخول الامتحان.',
                    'photo_status' => $user->photo_status,
                ], 403);
            }

            return response()->view('identity.profile.unapproved-face', [
                'user' => $user,
            ], 403);
        }

        return $next($request);
    }
}
