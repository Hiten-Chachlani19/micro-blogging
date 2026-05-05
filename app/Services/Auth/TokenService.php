<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Redis;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class TokenService {
    public function issueAccess(User $user) : string 
    {    
        return JWTAuth::fromUser($user);
    }

    private const REFRESH_PREFIX = 'refresh:';
    
    public function issueRefresh(User $user, string $familyId = null): string
    {
        $token = Str::random(64);
        $familyId = $familyId ?? Str::uuid()->toString();
        $ttl      = config('jwt.refresh_ttl') * 60; // seconds

        Redis::setex(
            self::REFRESH_PREFIX.$token,
            $ttl,
            json_encode([
                'user_id'    => $user->id,
                'family_id'  => $familyId,
                'created_at' => now()->toIso8601String(),
            ]),
        );

        return $token;
    }

    public function getRefreshPayload(string $token): ?array
    {
        $raw = Redis::get(self::REFRESH_PREFIX.$token);

        return $raw ? json_decode($raw, true) : null;
    }

    public function rotate(string $refreshToken): array
    {
        $payload = $this->getRefreshPayload($refreshToken);

        if ($payload === null) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid refresh token.');
        }

        // Reuse detected — assume token theft, revoke entire family
        if (!empty($payload['rotated'])) {
            $this->revokeFamily($payload['family_id']);
            throw new UnauthorizedHttpException('Bearer', 'Refresh token reuse detected. Please log in again.');
        }

        $user = \App\Models\User::find($payload['user_id']);
        if ($user === null) {
            $this->revokeRefresh($refreshToken);
            throw new UnauthorizedHttpException('Bearer', 'Invalid refresh token.');
        }

        // Mark old token as rotated for the grace window (30 s) instead of
        // deleting immediately — prevents a second in-flight request from
        // triggering false-positive reuse detection.
        Redis::setex(
            self::REFRESH_PREFIX.$refreshToken,
            30,
            json_encode(array_merge($payload, ['rotated' => true])),
        );

        $newRefresh = $this->issueRefresh($user, $payload['family_id']);
        $newAccess  = $this->issueAccess($user);

        return [
            'access_token'  => $newAccess,
            'refresh_token' => $newRefresh,
            'token_type'    => 'Bearer',
            'expires_in'    => config('jwt.ttl') * 60,
        ];
    }

    public function revokeRefresh(string $token): void
    {
        Redis::del(self::REFRESH_PREFIX.$token);
    }

    public function revokeFamily(string $familyId, string $exceptToken = null): void
    {
        // SCAN uses raw Redis key names — the client-level prefix must be prepended
        // to the pattern manually, otherwise no keys are matched.
        $prefix  = app('redis')->connection()->getOptions()->prefix ?? '';
        $pattern = $prefix.self::REFRESH_PREFIX.'*';

        $cursor = '0';
        do {
            [$cursor, $keys] = Redis::scan($cursor, 'match', $pattern, 'count', 100);
            foreach ($keys as $rawKey) {
                // Strip the prefix so that subsequent Redis commands (which re-apply it) work correctly
                $logicalKey = $prefix ? substr($rawKey, strlen($prefix)) : $rawKey;

                if ($exceptToken && str_ends_with($logicalKey, $exceptToken)) {
                    continue;
                }
                $payload = json_decode(Redis::get($logicalKey), true);
                if (($payload['family_id'] ?? null) === $familyId) {
                    Redis::del($logicalKey);
                }
            }
        } while ($cursor !== '0');
    }
}