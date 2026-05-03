<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatePostRequest;
use App\Http\Resources\PostResource;
use App\Http\Responses\ApiResponse;
use App\Services\PostService;
use Illuminate\Http\JsonResponse;

class PostController extends Controller
{
    public function __construct(private readonly PostService $posts) {}

    public function store(CreatePostRequest $request): JsonResponse
    {
        $post = $this->posts->createPost(
            auth()->user(),
            $request->validated(),
        );

        return ApiResponse::success(new PostResource($post), status: 201);
    }
}
