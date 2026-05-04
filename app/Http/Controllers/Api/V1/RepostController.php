<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\RepostRequest;
use App\Http\Resources\PostResource;
use App\Http\Responses\ApiResponse;
use App\Services\PostService;
use Illuminate\Http\JsonResponse;

class RepostController extends Controller
{
    public function __construct(private readonly PostService $posts) {}

    public function store(RepostRequest $request, int $postId): JsonResponse
    {
        $original = $this->posts->findWithDetails($postId);

        $repost = $this->posts->repost(
            auth()->user(),
            $original,
            $request->validated()['body'] ?? null,
        );

        $repost->load('author', 'originalPost.author');

        return ApiResponse::success(new PostResource($repost), status: 201);
    }
}
