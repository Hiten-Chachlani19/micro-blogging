<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'handle'       => [
                'required',
                'string',
                'max:30',
                'regex:/^[a-z0-9_]+$/',
                'unique:users,handle',
            ],
            'display_name' => ['sometimes', 'string', 'max:80'],
            'email'        => ['required', 'email:rfc', 'unique:users,email'],
            'password'     => [
                'required',
                'confirmed',
                Password::min(12)->mixedCase()->numbers()->symbols(),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'handle.regex' => 'Handle may only contain lowercase letters, numbers, and underscores.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'handle' => strtolower($this->handle ?? ''),
        ]);
    }
}
