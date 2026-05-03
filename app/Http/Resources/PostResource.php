<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'user_id'       => $this->user_id,
            'body'          => $this->body,
            'parent_id'     => $this->parent_id,
            'repost_of_id'  => $this->repost_of_id,
            'like_count'    => $this->like_count,
            'reply_count'   => $this->reply_count,
            'created_at'    => $this->created_at->toIso8601String(),
            'updated_at'    => $this->updated_at->toIso8601String(),
        ];
    }
}
