<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body'         => ['required', 'string', 'max:280'],
            'parent_id'    => ['nullable', 'integer', 'exists:posts,id'],
            'repost_of_id' => [
                'nullable',
                'integer',
                'exists:posts,id',
                Rule::prohibitedIf(fn () => filled($this->parent_id)),
            ],
        ];
    }
}
