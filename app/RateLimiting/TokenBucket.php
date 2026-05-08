<?php

namespace App\RateLimiting;

use Illuminate\Support\Facades\Redis;

/**
 * Token-bucket rate limiter backed by an atomic Redis Lua script.
 *
 * Atomicity guarantee: Redis executes Lua scripts as a single command.
 * No two calls can interleave, so there is no TOCTOU (Time Of Check → Time Of Update) race between reading
 * and updating the bucket state.
 */
class TokenBucket
{
    /** Cached SHA1 of the loaded Lua script (EVALSHA avoids re-sending the script body). */
    private static ?string $scriptSha = null;

    /**
     * Consume tokens from the named bucket.
     *
     * @param  string  $key          Full Redis key, e.g. "rl:user:42:write"
     * @param  int     $capacity     Maximum tokens the bucket can hold
     * @param  float   $refillPerSec Tokens added per second (may be fractional)
     * @param  int     $tokens       Tokens to consume per call (default 1)
     */
    public static function consume(
        string $key,
        int    $capacity,
        float  $refillPerSec,
        int    $tokens = 1,
    ): TokenBucketResult {
        $redis  = Redis::connection('default');
        $nowMs  = (int) (microtime(true) * 1000);
        $script = self::script();

        // Predis signature: eval(script, numkeys, key1, ..., arg1, ...)
        // EVALSHA avoids re-sending the script body on every request.
        try {
            $sha    = self::loadSha($redis, $script);
            $result = $redis->evalsha($sha, 1, $key, $capacity, $refillPerSec, $nowMs, $tokens);
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'NOSCRIPT')) {
                self::$scriptSha = null;
                $result = $redis->eval($script, 1, $key, $capacity, $refillPerSec, $nowMs, $tokens);
            } else {
                throw $e;
            }
        }

        return new TokenBucketResult(
            allowed:      (bool) ($result[0] ?? 0),
            remaining:    (int)  ($result[1] ?? 0),
            retryAfterMs: (int)  ($result[2] ?? 0),
        );
    }

    private static function loadSha($redis, string $script): string
    {
        if (self::$scriptSha === null) {
            self::$scriptSha = $redis->script('load', $script);
        }

        return self::$scriptSha;
    }

    private static function script(): string
    {
        return file_get_contents(base_path('resources/lua/token_bucket.lua'));
    }
}
