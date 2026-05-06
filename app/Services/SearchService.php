<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Support\Facades\DB;

class SearchService
{
    public function search(string $q, ?string $cursor, int $limit): array
    {
        // Offset-based pagination — simple, but unsafe under concurrent writes.
        // Used here deliberately to match the "naive" tier of the benchmark.
        $offset = $cursor ? (int) base64_decode($cursor) : 0;

        $pattern = '%' . addcslashes($q, '%_\\') . '%';

        // SQLite (used in tests) does not support ILIKE; fall back to LIKE.
        // SQLite LIKE is case-insensitive for ASCII characters by default.
        $operator = DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';

        $rows = Post::with('author')
            ->where('body', $operator, $pattern)
            ->whereNull('deleted_at')
            ->whereNull('parent_id')
            ->whereNull('repost_of_id')
            ->orderByDesc('id')
            ->offset($offset)
            ->limit($limit + 1)
            ->get();

        $hasMore = $rows->count() > $limit;
        $rows    = $rows->take($limit);

        $nextCursor = $hasMore ? base64_encode((string) ($offset + $limit)) : null;

        return [
            'data'        => $rows->values(),
            'next_cursor' => $nextCursor,
            'has_more'    => $hasMore,
        ];
    }
}
