<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class LoginService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly TokenService $tokens,
    ) {}

    public function login(string $email, string $password): array
    {
        $user = $this->users->findByEmail($email);

        if (! $user || ! Hash::check($password, $user->password)) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid credentials.');
        }

        return [
            'access_token'  => $this->tokens->issueAccess($user),
            'refresh_token' => $this->tokens->issueRefresh($user),
            'token_type'    => 'Bearer',
            'expires_in'    => config('jwt.ttl') * 60,
        ];
    }
}
