<?php

declare(strict_types=1);

namespace App\Support\Http\Middleware;

use App\Support\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route guard: `->middleware('role:teacher,institution')`.
 *
 * This is the ONLY place outside a Policy where a role is allowed to gate access.
 */
final class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $allowed = array_map(
            static fn (string $role): Role => Role::from($role),
            $roles,
        );

        abort_unless(in_array($user->role, $allowed, true), 403, 'ليست لديك صلاحية الوصول إلى هذه الصفحة.');

        return $next($request);
    }
}
