<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Support\Facades\DB;

class SearchService
{
    /**
     * Search posts using PostgreSQL full-text search with rank+id cursor pagination.
     *
     * Returns an array with keys: data (Collection<Post>), next_cursor (?string), has_more (bool).
     */
    public function search(string $q, ?string $cursor, int $limit): array
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return ['data' => collect(), 'next_cursor' => null, 'has_more' => false];
        }

        $decoded = $cursor ? json_decode(base64_decode($cursor), true) : null;

        // Read the regconfig from config so it stays in sync with the trigger function.
        // Both must use the same dictionary — mismatched configs produce wrong rankings.
        $regconfig = config('search.regconfig', 'english');

        $sql = "
            SELECT posts.id, ts_rank_cd(search_vector, query) AS rank
            FROM posts, websearch_to_tsquery('{$regconfig}', ?) query
            WHERE search_vector @@ query
              AND posts.deleted_at IS NULL
        ";

        $bindings = [$q];

        if ($decoded) {
            // Composite cursor: rows strictly after (rank DESC, id DESC)
            // i.e. rank < last_rank  OR  (rank = last_rank AND id < last_id)
            $sql .= "
                AND (
                    ts_rank_cd(search_vector, query) < ?
                    OR (ts_rank_cd(search_vector, query) = ? AND posts.id < ?)
                )
            ";
            $bindings[] = $decoded['rank'];
            $bindings[] = $decoded['rank'];
            $bindings[] = $decoded['id'];
        }

        $sql .= ' ORDER BY rank DESC, posts.id DESC LIMIT ?';
        $bindings[] = $limit + 1;

        $rows     = DB::select($sql, $bindings);
        $hasMore  = count($rows) > $limit;
        $rows     = array_slice($rows, 0, $limit);

        if (empty($rows)) {
            return ['data' => collect(), 'next_cursor' => null, 'has_more' => false];
        }

        $rankById = collect($rows)->keyBy('id')->map(fn ($r) => $r->rank);
        $ids      = array_column($rows, 'id');

        // Load Eloquent models with author; re-order to match rank sort.
        $posts = Post::with('author')
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $ordered = collect($ids)->map(fn ($id) => $posts->get($id))->filter()->values();

        $nextCursor = null;
        if ($hasMore) {
            $last       = end($rows);
            $nextCursor = base64_encode(json_encode(['rank' => $last->rank, 'id' => $last->id]));
        }

        return [
            'data'        => $ordered,
            'next_cursor' => $nextCursor,
            'has_more'    => $hasMore,
        ];
    }
}
