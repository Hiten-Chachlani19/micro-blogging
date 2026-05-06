<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchRequest;
use App\Http\Resources\PostResource;
use App\Http\Responses\ApiResponse;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;

class SearchController extends Controller
{
    public function __construct(private readonly SearchService $search) {}

    public function index(SearchRequest $request): JsonResponse
    {
        $q      = $request->validated('q');
        $cursor = $request->validated('cursor');
        $limit  = (int) $request->validated('limit', 20);

        $result = $this->search->search($q, $cursor ?: null, $limit);

        return ApiResponse::success(
            PostResource::collection($result['data'])->resolve(),
            meta: [
                'next_cursor' => $result['next_cursor'],
                'has_more'    => $result['has_more'],
            ],
        );
    }
}
