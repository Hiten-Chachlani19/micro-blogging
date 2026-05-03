<?php

namespace App\Repositories\Contracts;

use App\Models\Post;
use Illuminate\Pagination\CursorPaginator;

interface PostRepositoryInterface
{
    public function create(array $data): Post;

    public function findById(int $id): ?Post;

    public function paginate(array $filters, ?string $cursor, int $limit): CursorPaginator;

    public function update(Post $post, array $data): Post;

    public function delete(Post $post): void;
}
