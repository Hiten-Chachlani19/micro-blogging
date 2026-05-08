<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Hashtag extends Model
{
    public $timestamps = false;

    protected $fillable = ['tag'];

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }
}
