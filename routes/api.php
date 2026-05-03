<?php

use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\PostController;

Route::prefix('v1')->group(function () {
    Route::get('health', HealthController::class);

    Route::prefix('auth')->group(function () {
        Route::post('register', RegisterController::class);
        Route::post('login', LoginController::class);
    });

    Route::middleware('auth.jwt')->group(function () {
        Route::get('me', MeController::class);

        Route::post('posts', [PostController::class, 'store']);
    });

});
