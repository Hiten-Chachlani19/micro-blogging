<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Responses\ApiResponse;
use App\Services\Auth\LoginService;
use Illuminate\Http\JsonResponse;

class LoginController extends Controller
{
    public function __construct(private readonly LoginService $login) {}

    public function __invoke(LoginRequest $request): JsonResponse
    {
        $tokens = $this->login->login(
            $request->validated('email'),
            $request->validated('password'),
        );

        return ApiResponse::success($tokens);
    }
}
