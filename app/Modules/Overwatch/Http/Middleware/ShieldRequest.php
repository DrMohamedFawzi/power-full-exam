<?php

declare(strict_types=1);

namespace App\Modules\Overwatch\Http\Middleware;

use App\Modules\Overwatch\Actions\RecordThreat;
use App\Modules\Overwatch\Enums\AttackType;
use App\Modules\Overwatch\Jobs\RecordThreatJob;
use App\Modules\Overwatch\Models\BannedIp;
use App\Modules\Overwatch\Services\SignatureScanner;
use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Overwatch's front door. Runs on every web request:
 *
 *   1. reject IPs with an active ban (cached lookup)
 *   2. rate-limit per IP
 *   3. scan the payload for attack signatures, log threats, auto-ban repeat offenders
 *
 * WHAT THE WAF SCANS AND WHY:
 *   - Always: the request path and every query-string value.
 *   - Body fields: scanned too, EXCEPT when the path looks like exam runtime
 *     traffic (contains "exam"). Exam answers and proctoring signals carry
 *     arbitrary student prose, code-flavoured answers, and JSON blobs that can
 *     legitimately contain quotes, angle brackets, or SQL-ish words — scanning
 *     them aggressively risks false positives against real students sitting a
 *     real exam, which is worse than missing an attack on that one surface.
 *     Path and rate-limit protection still apply there. Passwords are never
 *     scanned (they are opaque secrets, not attack surface, and scanning them
 *     would leak them into the threat log).
 *
 * This middleware must never throw: a bug in detection must never take the
 * whole site down. Anything unexpected is caught, logged, and the request is
 * allowed to proceed.
 */
final class ShieldRequest
{
    private const BAN_CACHE_TTL_SECONDS = 30;

    private const EXEMPT_PATH_MARKER = 'exam';

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('aegis.overwatch.enabled')) {
            return $next($request);
        }

        $ip = (string) $request->ip();

        if (in_array($ip, (array) config('aegis.overwatch.trusted_ips'), true)) {
            return $next($request);
        }

        if ($this->isBanned($ip)) {
            return response()->view('overwatch.errors.banned', [], 403);
        }

        if ($this->isRateLimited($request, $ip)) {
            return response()->view('overwatch.errors.throttled', [], 429);
        }

        try {
            $this->scan($request, $ip);
        } catch (\Throwable $exception) {
            Log::warning('overwatch.shield_request.scan_failed', [
                'ip' => $ip,
                'error' => $exception->getMessage(),
            ]);
        }

        $response = $next($request);
        $response->headers->set('Content-Security-Policy', "default-src 'self' 'unsafe-inline' 'unsafe-eval' blob: data: https:; script-src 'self' 'unsafe-inline' 'unsafe-eval' blob: data: https:; worker-src 'self' blob: data: https:; style-src 'self' 'unsafe-inline' https:; img-src 'self' data: blob: https:; font-src 'self' data: https:; connect-src 'self' blob: data: https:;");
        return $response;
    }

    private function isBanned(string $ip): bool
    {
        return (bool) Cache::remember(
            "overwatch:banned:{$ip}",
            self::BAN_CACHE_TTL_SECONDS,
            static fn (): bool => BannedIp::query()->active()->where('ip_address', $ip)->exists(),
        );
    }

    private function isRateLimited(Request $request, string $ip): bool
    {
        /** @var RateLimiter $limiter */
        $limiter = app(RateLimiter::class);

        $key = "overwatch:rate:{$ip}";
        $maxAttempts = (int) config('aegis.overwatch.rate_limit_per_minute');

        if ($limiter->tooManyAttempts($key, $maxAttempts)) {
            app(RecordThreat::class)(
                ipAddress: $ip,
                attackType: AttackType::RateAbuse,
                requestPath: $request->path(),
                payload: "تجاوز الحد: {$maxAttempts} طلب/دقيقة",
                userAgent: $request->userAgent(),
                userId: $request->user()?->id,
            );

            return true;
        }

        $limiter->hit($key, 60);

        return false;
    }

    private function scan(Request $request, string $ip): void
    {
        $scanner = app(SignatureScanner::class);
        $path = $request->path();

        $bodyFields = $this->isExamRuntimePath($path)
            ? []
            : $request->except(['password', 'password_confirmation']);

        $attackType = $scanner->scan($path)
            ?? $scanner->scanArray($request->query())
            ?? $scanner->scanArray($bodyFields);

        if ($attackType === null) {
            return;
        }

        RecordThreatJob::dispatch(
            $ip,
            $attackType,
            $path,
            $this->firstOffendingExcerpt($scanner, $path, $request->query(), $bodyFields),
            $request->userAgent(),
            $request->user()?->id,
        );
    }

    private function isExamRuntimePath(string $path): bool
    {
        return str_contains($path, self::EXEMPT_PATH_MARKER);
    }

    /**
     * Rebuilds a short, human-readable excerpt of whichever value tripped the
     * scanner, for the threat log. Best-effort only — never throws.
     *
     * @param  array<array-key, mixed>  $query
     * @param  array<array-key, mixed>  $body
     */
    private function firstOffendingExcerpt(SignatureScanner $scanner, string $path, array $query, array $body): ?string
    {
        if ($scanner->scan($path) !== null) {
            return $path;
        }

        foreach (array_merge($query, $body) as $key => $value) {
            if (is_string($value) && $scanner->scan($value) !== null) {
                return "{$key}: {$value}";
            }
        }

        return null;
    }
}
