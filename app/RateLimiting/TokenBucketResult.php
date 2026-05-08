<?php

namespace App\RateLimiting;

final readonly class TokenBucketResult
{
    public function __construct(
        public bool $allowed,
        public int  $remaining,
        public int  $retryAfterMs,
    ) {}

    public function retryAfterSeconds(): int
    {
        return (int) ceil($this->retryAfterMs / 1000);
    }

    /** Unix timestamp (seconds) when the bucket resets enough for one token. */
    public function resetAt(): int
    {
        return time() + $this->retryAfterSeconds();
    }
}
