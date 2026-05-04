<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\LikeService;
use App\Services\PostService;
use Illuminate\Http\JsonResponse;

class LikeController extends Controller
{
    public function __construct(
        private readonly PostService $posts,
        private readonly LikeService $likes,
    ) {}

    public function store(int $postId): JsonResponse
    {
        $post = $this->posts->findWithDetails($postId);
        $this->likes->like(auth()->user(), $post);

        return ApiResponse::success(null, status: 204);
    }

    public function destroy(int $postId): JsonResponse
    {
        $post = $this->posts->findWithDetails($postId);
        $this->likes->unlike(auth()->user(), $post);

        return ApiResponse::success(null, status: 204);
    }
}
