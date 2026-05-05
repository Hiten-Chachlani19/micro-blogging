<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\Auth\TokenBlacklist;
use App\Services\Auth\TokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class LogoutController extends Controller
{
    public function __construct(
        private readonly TokenBlacklist $blacklist,
        private readonly TokenService $tokens,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        // Blacklist the current access token for its remaining lifetime
        $payload        = JWTAuth::parseToken()->getPayload();
        $jti            = $payload->get('jti');
        $remainingTtl   = $payload->get('exp') - now()->timestamp;

        $this->blacklist->revoke($jti, max(0, $remainingTtl));

        // Revoke the refresh token family if the client sent one
        $refreshToken = $request->input('refresh_token');
        if ($refreshToken) {
            $refreshPayload = $this->tokens->getRefreshPayload($refreshToken);
            if ($refreshPayload) {
                $this->tokens->revokeFamily($refreshPayload['family_id']);
            }
        }

        return ApiResponse::success(null, [], 204);
    }
}
    