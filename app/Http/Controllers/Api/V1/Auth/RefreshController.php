<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RefreshRequest;
use App\Http\Responses\ApiResponse;
use App\Services\Auth\TokenService;
use Illuminate\Http\JsonResponse;

class RefreshController extends Controller
{
    public function __construct(private readonly TokenService $tokens) {}

    public function __invoke(RefreshRequest $request): JsonResponse
    {
        $tokens = $this->tokens->rotate($request->validated('refresh_token'));

        return ApiResponse::success($tokens);
    }
}
