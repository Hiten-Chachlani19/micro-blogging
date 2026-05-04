<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LikeService
{
    public function like(User $user, Post $post): void
    {
        $inserted = DB::affectingStatement(
            'INSERT INTO likes (user_id, post_id, created_at) VALUES (?, ?, CURRENT_TIMESTAMP) ON CONFLICT DO NOTHING',
            [$user->id, $post->id]
        );

        if ($inserted === 1) {
            DB::table('posts')->where('id', $post->id)->increment('like_count');
        }
    }

    public function unlike(User $user, Post $post): void
    {
        $deleted = DB::table('likes')
            ->where('user_id', $user->id)
            ->where('post_id', $post->id)
            ->delete();

        if ($deleted === 1) {
            DB::table('posts')->where('id', $post->id)->decrement('like_count');
        }
    }
}
