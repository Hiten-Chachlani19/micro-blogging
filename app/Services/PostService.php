<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\Hashtag;
use App\Models\Post;
use App\Models\User;
use App\Repositories\Contracts\PostRepositoryInterface;
use App\Services\Posts\ContentParser;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class PostService
{
    public function __construct(private readonly PostRepositoryInterface $posts, private readonly ContentParser $parser) {}

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

        $userHandles = $this->parser->extractMentions($post['body']);
        if(!empty($userHandles)) {
            $userIds =  DB::table('users')
                    ->whereIn('handle', $userHandles)
                    ->pluck('id');

            DB::table('mentions')->insertOrIgnore(
                $userIds->map(fn ($userId) => [
                    'post_id' => $post->id,
                    'mentioned_user_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now()
                ])->toArray()
            );
            
        }
                
        $hashTags = $this->parser->extractHashtags($post['body']);
        if(!empty($hashTags)) {
            $hashTagsId = collect($hashTags)
                        ->map(function($hashtag) {
                            return Hashtag::firstOrCreate(
                                ['tag' => strtolower($hashtag)]
                            )->id;
                        });
            $post->hashtags()->syncWithoutDetaching($hashTagsId);
        }
        
        return $post;
    }

    public function list(array $filters = [], ?string $cursor = null, int $limit = 20): CursorPaginator
    {
        return $this->posts->paginate($filters, $cursor, $limit);
    }

    public function listReplies(int $parentId, ?string $cursor = null, int $limit = 20): CursorPaginator
    {
        return $this->posts->paginateReplies($parentId, $cursor, $limit);
    }

    public function findWithDetails(int $id): Post
    {
        return $this->posts->findByIdWithDetails($id);
    }

    public function update(User $actor, Post $post, array $data): Post
    {
        if ($actor->id !== $post->user_id) {
            throw new AuthorizationException('You do not own this post.');
        }

        return $this->posts->update($post, $data);
    }

    public function repost(User $actor, Post $original, ?string $body): Post
    {
        $alreadyReposted = DB::table('posts')
            ->where('user_id', $actor->id)
            ->where('repost_of_id', $original->id)
            ->exists();

        if ($alreadyReposted) {
            throw new ConflictHttpException('You have already reposted this post.');
        }

        return $this->posts->create([
            'user_id'      => $actor->id,
            'body'         => $body,
            'repost_of_id' => $original->id,
        ]);
    }

    public function delete(User $actor, Post $post): void
    {
        if ($actor->id !== $post->user_id && $actor->role !== Role::Admin) {
            throw new AuthorizationException('You do not own this post.');
        }

        if ($post->parent_id) {
            DB::table('posts')
                ->where('id', $post->parent_id)
                ->decrement('reply_count');
        }

        $this->posts->delete($post);
    }
}