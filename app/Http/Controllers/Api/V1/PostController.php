<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use App\Services\PostService;
use App\Support\CacheHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

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

     public function index(Request $request): JsonResponse
    {
        $limit  = min((int) $request->query('limit', 20), 100);
        $cursor = $request->query('cursor');

        $cacheKey = 'posts:list:' . md5(json_encode(array_filter([
            'cursor' => $cursor,
            'limit'  => $limit,
        ])));

        $cached = CacheHelper::rememberWithLock($cacheKey, Cache::ttl(300), function () use ($cursor, $limit) {
            $paginator = $this->posts->list([], $cursor ?: null, $limit);

            return [
                'data' => PostResource::collection($paginator)->resolve(),
                'meta' => [
                    'next_cursor' => $paginator->nextCursor()?->encode(),
                    'prev_cursor' => $paginator->previousCursor()?->encode(),
                    'has_more'    => $paginator->hasMorePages(),
                ],
            ];
        });

        return ApiResponse::success($cached['data'], meta: $cached['meta']);
    }

    public function show(int $id): JsonResponse
    {
        $post = $this->posts->findWithDetails($id);

        return ApiResponse::success(new PostResource($post));
    }

    public function update(UpdatePostRequest $request, int $id): JsonResponse
    {
        $post = $this->posts->findWithDetails($id);
        $post = $this->posts->update(auth()->user(), $post, $request->validated());

        return ApiResponse::success(new PostResource($post));
    }

    public function destroy(int $id): JsonResponse
    {
        $post = $this->posts->findWithDetails($id);
        $this->posts->delete(auth()->user(), $post);

        return ApiResponse::success(null);
    }

    public function mentionedUsers(int $id): JsonResponse
    {
        $post = $this->posts->findWithDetails($id);
        $mentionedUsers = $post->mentionedUsers;
        
        return ApiResponse::success(UserResource::collection($mentionedUsers), status: 200);
    }

    public function hashtags(int $id): JsonResponse
    {
        $post = $this->posts->findWithDetails($id);
        $hashtags = $post->hashtags;
        
        return ApiResponse::success($hashtags, status: 200);
    }

}
