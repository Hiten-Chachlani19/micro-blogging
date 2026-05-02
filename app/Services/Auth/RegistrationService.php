<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

class RegistrationService
{
    public function __construct(private readonly UserRepositoryInterface $users) {}

    public function register(array $data): User
    {
        return $this->users->create([
            'handle'       => $data['handle'],
            'display_name' => $data['display_name'] ?? null,
            'email'        => $data['email'],
            'password'     => $data['password'],
            'role'         => 'creator',
        ]);
    }
}
