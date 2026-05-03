<?php

namespace App\Models;

use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'body',
        'parent_id',
        'repost_of_id',
    ];

    protected function casts(): array
    {
        return [
            'like_count'  => 'integer',
            'reply_count' => 'integer',
            'deleted_at'  => 'datetime',
        ];
    }

    // ── Relations ─────────────────────────────────────────────────────────────

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'parent_id');
    }

    public function originalPost(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'repost_of_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Post::class, 'parent_id');
    }

    public function reposts(): HasMany
    {
        return $this->hasMany(Post::class, 'repost_of_id');
    }
}
