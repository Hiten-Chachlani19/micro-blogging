<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Handled by Illuminate\Http\Middleware\HandleCors, which sits at the top
    | of Laravel's global middleware stack (before routing). It responds to
    | OPTIONS preflights automatically and injects Access-Control-* headers on
    | every matching response.
    |
    | CORS_ALLOWED_ORIGINS — comma-separated list of allowed origins.
    | Example: CORS_ALLOWED_ORIGINS=http://localhost:3000,https://studylink.app
    |
    */

    'paths' => ['api/*', 'v1/*'],

    'allowed_methods' => ['GET', 'POST', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => [
    'http://localhost:5173',
    'https://cms-frontend-sepia.vercel.app',
],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'Authorization', 'Accept', 'X-Requested-With'],

    /*
    | Rate-limit headers must be exposed so browser JS can read them.
    | By default only "safe" headers (Content-Type, Cache-Control, etc.)
    | are accessible to JS — custom headers require explicit listing here.
    */
    'exposed_headers' => ['X-RateLimit-Remaining', 'X-RateLimit-Reset', 'Retry-After'],

    /*
    | Browser caches the preflight result for this many seconds (24 h).
    | Reduces OPTIONS round-trips on subsequent requests from the same origin.
    */
    'max_age' => 86400,

    /*
    | Required for JWT bearer tokens sent via Authorization header.
    | When true, allowed_origins MUST be explicit — never '*'.
    */
    'supports_credentials' => true,

];
