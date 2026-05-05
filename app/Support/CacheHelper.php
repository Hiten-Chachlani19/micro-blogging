<?php

namespace App\Support;

use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;

class CacheHelper
{

    public static function rememberWithLock(string $key, int $ttl, callable $callback, int $lockTtl = 5): mixed
    {
        // Fast path — key already cached
        $cached = Cache::get($key);
        if ($cached !== null) {
            return $cached;
        }

        $lock = Cache::lock("rebuild:{$key}", $lockTtl);

        if ($lock->get()) {
            try {
                // Double-check: a parallel worker may have filled the key while we
                // waited for the lock to be handed to us.
                $cached = Cache::get($key);
                if ($cached !== null) {
                    return $cached;
                }

                $value = $callback();
                Cache::put($key, $value, $ttl);

                return $value;
            } finally {
                $lock->release();
            }
        }

        // Could not acquire the lock — someone else is rebuilding.
        // Wait for them to finish, then read their result.
        try {
            $lock->block($lockTtl);
            $lock->release();
        } catch (LockTimeoutException) {
            // Timed out waiting — rebuild ourselves as a last resort so the
            // request does not fail. This should be extremely rare.
        }

        $cached = Cache::get($key);
        if ($cached !== null) {
            return $cached;
        }

        // Final fallback: rebuild and store (idempotent — worst case two workers
        // both reach here and the second write simply overwrites the first with
        // an identical value).
        $value = $callback();
        Cache::put($key, $value, $ttl);

        return $value;
    }
}
