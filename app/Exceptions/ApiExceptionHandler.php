<?php

namespace App\Exceptions;

use App\Http\Responses\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Throwable;

class ApiExceptionHandler
{
    public static function register(Exceptions $exceptions): void
    {
        $exceptions->render(function (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        });

        $exceptions->render(function (UnauthorizedHttpException $e) {
            return ApiResponse::error($e->getMessage(), 401);
        });

        $exceptions->render(function (AuthenticationException $e) {
            return ApiResponse::error('Unauthenticated.', 401);
        });

        $exceptions->render(function (AuthorizationException $e) {
            return ApiResponse::error('Forbidden.', 403);
        });

        $exceptions->render(function (ModelNotFoundException|NotFoundHttpException $e) {
            return ApiResponse::error('Not found.', 404);
        });

        $exceptions->render(function (Throwable $e) {
            $message = config('app.debug') ? $e->getMessage() : 'Server error.';

            return ApiResponse::error($message, 500);
        });
    }
}
