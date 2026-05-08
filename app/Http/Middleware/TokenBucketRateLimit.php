<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use App\RateLimiting\TokenBucket;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Token-bucket rate limiter middleware.
 *
 * Usage in routes:
 *   ->middleware('throttle.bucket:write,60,1,user')
 *
 * Parameters (positional via route middleware string):
 *   $bucketName   — logical name, used in the Redis key (e.g. "write", "auth")
 *   $capacity     — max tokens the bucket holds
 *   $refillPerSec — tokens added per second (float; e.g. 0.2 for 1 token/5s)
 *   $scope        — "user" | "ip" | "user_or_ip"
 *                   user        → key on authenticated user ID (falls back to IP if unauthenticated)
 *                   ip          → key on client IP (good for auth endpoints)
 *                   user_or_ip  → keyed on user ID when authenticated, IP otherwise
 */
class TokenBucketRateLimit
{
    public function handle(
        Request $request,
        Closure $next,
        string  $bucketName   = 'default',
        int     $capacity     = 60,
        float   $refillPerSec = 1.0,
        string  $scope        = 'user_or_ip',
    ): Response {
        // RATE_LIMIT_ENABLED=false disables enforcement (set in phpunit.xml for tests)
        if (! config('app.rate_limit_enabled', true)) {
            return $next($request);
        }

        $scopeValue = $this->resolveScopeValue($request, $scope);
        $key        = "rl:{$scopeValue}:{$bucketName}";

        $result = TokenBucket::consume($key, $capacity, $refillPerSec);

        if (! $result->allowed) {
            return response()->json([
                'data'   => null,
                'errors' => [[
                    'field'   => null,
                    'message' => 'Too many requests.',
                    'code'    => 'rate_limited',
                    'retry_after_ms' => $result->retryAfterMs,
                ]],
                'meta' => [],
            ], 429)->withHeaders([
                'Retry-After'          => $result->retryAfterSeconds(),
                'X-RateLimit-Remaining' => 0,
                'X-RateLimit-Reset'    => $result->resetAt(),
            ]);
        }

        $response = $next($request);

        $response->headers->set('X-RateLimit-Remaining', $result->remaining);
        $response->headers->set('X-RateLimit-Reset',     $result->resetAt());

        return $response;
    }

    private function resolveScopeValue(Request $request, string $scope): string
    {
        $user = $request->user();

        return match ($scope) {
            'user'        => $user ? "user:{$user->id}" : "ip:{$request->ip()}",
            'ip'          => "ip:{$request->ip()}",
            'user_or_ip'  => $user ? "user:{$user->id}" : "ip:{$request->ip()}",
            default       => "ip:{$request->ip()}",
        };
    }
}
