<?php

use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\RefreshController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\LikeController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\PostController;
use App\Http\Controllers\Api\V1\ReplyController;
use App\Http\Controllers\Api\V1\RepostController;

Route::prefix('v1')->group(function () {
    Route::get('health', HealthController::class);

    Route::prefix('auth')->group(function () {
        Route::post('register', RegisterController::class);
        Route::post('login', LoginController::class);
        Route::post('refresh', RefreshController::class);
    });

    Route::middleware('auth.jwt')->group(function () {
        Route::post('auth/logout', LogoutController::class);
        Route::get('me', MeController::class);

        Route::post('posts', [PostController::class, 'store']);
        
        Route::get('posts/{id}', [PostController::class, 'show']);
        Route::patch('posts/{id}', [PostController::class, 'update']);
        Route::delete('posts/{id}', [PostController::class, 'destroy']);

        Route::get('posts/{postId}/replies', [ReplyController::class, 'index']);
        Route::post('posts/{postId}/replies', [ReplyController::class, 'store']);
        
        Route::post('posts/{postId}/repost', [RepostController::class, 'store']);
        
        Route::post('posts/{postId}/likes', [LikeController::class, 'store']);
        Route::delete('posts/{postId}/likes', [LikeController::class, 'destroy']);
    });

    Route::get('posts', [PostController::class, 'index']);
});
