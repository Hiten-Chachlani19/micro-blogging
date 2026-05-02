<?php

namespace App\Services\Auth;

use App\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;

class TokenService {
    public function issueAccess(User $user) : string 
    {    
        return JWTAuth::fromUser($user);
    }

    public function issueRefresh(): string
    {
        $token = Str::random(64);

        return $token;
    }
}