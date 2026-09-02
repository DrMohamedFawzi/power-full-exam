<?php

declare(strict_types=1);

namespace App\Support\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A teacher may sign in before their institution approves them, but may not act
 * until it does. They see a holding page instead.
 */
final class EnsureTeacherIsApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->isTeacher() && ! $user->is_approved) {
            return redirect()->route('teacher.pending');
        }

        return $next($request);
    }
}
