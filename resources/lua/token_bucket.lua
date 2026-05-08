--[[
Token Bucket — atomic Redis Lua script.

Why Lua? Redis executes Lua atomically — no other command can interleave between
the HMGET, the arithmetic, and the HMSET. A non-Lua implementation using a plain
GET + SET pair has a classic TOCTOU race: two concurrent requests could both read
the same token count, both decide they are allowed, and both decrement — issuing
one more request than the bucket permits.

KEYS[1]  = bucket key, e.g. "rl:user:42:write"
ARGV[1]  = capacity          (integer, max tokens)
ARGV[2]  = refill_rate_per_sec  (float, tokens added per second)
ARGV[3]  = now_ms            (integer, current Unix time in milliseconds)
ARGV[4]  = requested_tokens  (integer, usually 1)

Returns a three-element array:
  [1]  allowed        — 1 if the request is permitted, 0 if rate-limited
  [2]  remaining      — tokens remaining after this request (0 when denied)
  [3]  retry_after_ms — milliseconds until one token refills (0 when allowed)
--]]

local key              = KEYS[1]
local capacity         = tonumber(ARGV[1])
local refill_rate      = tonumber(ARGV[2])   -- tokens per second (may be fractional)
local now_ms           = tonumber(ARGV[3])
local requested        = tonumber(ARGV[4])

-- Read current bucket state
local data = redis.call('HMGET', key, 'tokens', 'last_refill_ms')
local tokens        = tonumber(data[1])
local last_refill   = tonumber(data[2])

-- First request for this key — start with a full bucket
if tokens == nil then
    tokens      = capacity
    last_refill = now_ms
end

-- Refill proportionally to elapsed time
local elapsed_sec = (now_ms - last_refill) / 1000.0
local refilled    = elapsed_sec * refill_rate
tokens = math.min(capacity, tokens + refilled)
last_refill = now_ms

-- Decide
local allowed = 0
local retry_after_ms = 0

if tokens >= requested then
    tokens  = tokens - requested
    allowed = 1
else
    -- Time until one token refills
    local deficit = requested - tokens
    retry_after_ms = math.ceil((deficit / refill_rate) * 1000)
end

-- Persist with TTL = capacity / refill_rate * 2  (auto-expire idle buckets)
local ttl_sec = math.ceil((capacity / refill_rate) * 2)
redis.call('HMSET', key, 'tokens', tokens, 'last_refill_ms', last_refill)
redis.call('EXPIRE', key, ttl_sec)

-- Return integers for cleaner PHP handling (remaining floored)
return { allowed, math.floor(tokens), retry_after_ms }
