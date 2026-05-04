<?php

namespace App\Repositories;

use App\Models\Post;
use App\Repositories\Contracts\PostRepositoryInterface;
use Illuminate\Pagination\CursorPaginator;

class PostRepository implements PostRepositoryInterface
{
    public function __construct(private readonly Post $model) {}

    public function create(array $data): Post
    {
        return $this->model->create($data);
    }

    public function findById(int $id): ?Post
    {
        return $this->model->find($id);
    }

    private const PAGINATE_RELATIONS = ['author'];

    public function paginate(array $filters, ?string $cursor, int $limit): CursorPaginator
    {
        return $this->model
        ->with(self::PAGINATE_RELATIONS)
            ->whereNull('parent_id')
            ->whereNull('repost_of_id')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->cursorPaginate($limit, ['*'], 'cursor', $cursor ? \Illuminate\Pagination\Cursor::fromEncoded($cursor) : null);
    }

    public function paginateReplies(int $parentId, ?string $cursor, int $limit): CursorPaginator
    {
        return $this->model
            ->with(['author'])
            ->where('parent_id', $parentId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->cursorPaginate($limit, ['*'], 'cursor', $cursor ? \Illuminate\Pagination\Cursor::fromEncoded($cursor) : null);
    }

    public function findByIdWithDetails(int $id): Post
    {
        return $this->model
            ->with([
                'author',
                'parent.author',
                'originalPost.author',
                'replies' => fn ($q) => $q->with('author')->orderBy('created_at')->limit(10),
            ])
            ->findOrFail($id);
    }
    
    public function update(Post $post, array $data): Post
    {
        $post->update($data);

        return $post;
    }

    public function delete(Post $post): void
    {
        $post->delete();
    }
}
