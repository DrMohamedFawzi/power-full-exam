<?php

declare(strict_types=1);

use App\Modules\Overwatch\Http\Middleware\ShieldRequest;
use App\Support\Http\Middleware\EnsureTeacherIsApproved;
use App\Support\Http\Middleware\EnsureUserHasRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Overwatch inspects every web request: IP bans, rate limiting, WAF signatures.
        $middleware->web(append: [
            ShieldRequest::class,
        ]);

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'approved' => EnsureTeacherIsApproved::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
