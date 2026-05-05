<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Redis;

class TokenBlacklist
{
    private const PREFIX = 'blacklist:';

    public function revoke(string $jti, int $ttlSeconds): void
    {
        if ($ttlSeconds > 0) {
            Redis::setex(self::PREFIX.$jti, $ttlSeconds, '1');
        }
    }

    public function isRevoked(string $jti): bool
    {
        return (bool) Redis::exists(self::PREFIX.$jti);
    }
}
