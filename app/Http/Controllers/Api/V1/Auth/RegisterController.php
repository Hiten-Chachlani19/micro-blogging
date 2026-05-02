<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Responses\ApiResponse;
use App\Services\Auth\RegistrationService;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    public function __construct(private readonly RegistrationService $registration) {}

    public function __invoke(RegisterRequest $request): JsonResponse
    {
        $user = $this->registration->register($request->validated());

        return ApiResponse::success($user, status: 201);
    }
}
