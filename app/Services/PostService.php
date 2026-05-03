<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use App\Repositories\Contracts\PostRepositoryInterface;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Facades\DB;

class PostService
{
    public function __construct(private readonly PostRepositoryInterface $posts) {}

    public function createPost(User $author, array $data): Post
    {
        $post = $this->posts->create([
            'user_id'      => $author->id,
            'body'         => $data['body'],
            'parent_id'    => $data['parent_id'] ?? null,
            'repost_of_id' => $data['repost_of_id'] ?? null,
        ]);

        if ($post->parent_id) {
            DB::table('posts')
                ->where('id', $post->parent_id)
                ->increment('reply_count');
        }

        return $post;
    }

    public function list(array $filters = [], ?string $cursor = null, int $limit = 20): CursorPaginator
    {
        return $this->posts->paginate($filters, $cursor, $limit);
    }

    public function findWithDetails(int $id): Post
    {
        return $this->posts->findByIdWithDetails($id);
    }
}