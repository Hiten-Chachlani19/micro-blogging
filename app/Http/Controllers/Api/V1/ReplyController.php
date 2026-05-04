<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateReplyRequest;
use App\Http\Resources\PostResource;
use App\Http\Responses\ApiResponse;
use App\Services\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReplyController extends Controller
{
    public function __construct(private readonly PostService $posts) {}

    public function index(Request $request, int $postId): JsonResponse
    {
        // Verify parent exists (throws 404 if not)
        $this->posts->findWithDetails($postId);

        $limit  = min((int) $request->query('limit', 20), 100);
        $cursor = $request->query('cursor');

        $paginator = $this->posts->listReplies($postId, $cursor ?: null, $limit);

        return ApiResponse::success(
            PostResource::collection($paginator),
            meta: [
                'next_cursor' => $paginator->nextCursor()?->encode(),
                'prev_cursor' => $paginator->previousCursor()?->encode(),
                'has_more'    => $paginator->hasMorePages(),
            ],
        );
    }

    public function store(CreateReplyRequest $request, int $postId): JsonResponse
    {
        // Verify parent exists (throws 404 if not)
        $this->posts->findWithDetails($postId);

        $post = $this->posts->createPost(auth()->user(), [
            'body'      => $request->validated()['body'],
            'parent_id' => $postId,
        ]);

        return ApiResponse::success(new PostResource($post), status: 201);
    }
}
