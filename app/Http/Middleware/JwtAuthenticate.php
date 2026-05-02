<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;

class JwtAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (! $user) {
                return ApiResponse::error('Unauthenticated.', 401);
            }

            auth()->setUser($user);

        } catch (TokenExpiredException) {
            return ApiResponse::error('Token expired.', 401, [
                ['field' => null, 'message' => 'token_expired'],
            ]);
        } catch (TokenInvalidException) {
            return ApiResponse::error('Token invalid.', 401);
        } catch (JWTException) {
            return ApiResponse::error('Token not provided.', 401);
        }

        return $next($request);
    }
}
