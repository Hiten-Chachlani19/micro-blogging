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
use App\Http\Controllers\Api\V1\SearchController;

Route::prefix('v1')->group(function () {
    Route::get('health', HealthController::class);

    Route::prefix('auth')->group(function () {
        Route::post('register', RegisterController::class);
        Route::post('login', LoginController::class);
        Route::post('refresh', RefreshController::class);
    });

    Route::middleware('auth.jwt')->group(function () {
        Route::post('auth/logout', LogoutController::class)
            ->middleware('throttle.bucket:auth,20,0.2,ip');
        
        // Read endpoints - generous limit, user_or_ip_scope
        // cap=300, refill=5/s, scope=user_or_ip
        Route::middleware('throttle.bucket:read,300,5,user_or_ip')->group(function() {
            Route::get('me', MeController::class);
            Route::get('search', [SearchController::class, 'index']);
            Route::get('posts', [PostController::class, 'index']);
            Route::get('posts/{id}', [PostController::class, 'show']);
            Route::get('posts/mentionedUsers/{id}', [PostController::class, 'mentionedUsers']);
            Route::get('posts/hashtags/{id}', [PostController::class, 'hashtags']);
            Route::get('posts/{postId}/replies', [ReplyController::class, 'index']);
        });

        // Write endpoints - moderate limit, user scope
        // cap=60, refill=1/s, scope=user
        Route::middleware('throttle.bucket:write,60,1,user')->group(function() {
            Route::post('posts', [PostController::class, 'store']);        
            Route::patch('posts/{id}', [PostController::class, 'update']);
            Route::delete('posts/{id}', [PostController::class, 'destroy']);

            Route::post('posts/{postId}/replies', [ReplyController::class, 'store']);
        
            Route::post('posts/{postId}/repost', [RepostController::class, 'store']);
        
            Route::post('posts/{postId}/likes', [LikeController::class, 'store']);
            Route::delete('posts/{postId}/likes', [LikeController::class, 'destroy']);
        });
        
    });
});
