<?php
namespace App\Providers;

use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class SSOUserProvider implements UserProvider
{
    public function retrieveById($identifier)
    {
        return session('sso_user');
    }

    public function retrieveByToken($identifier, $token)
    {
        return session('sso_user');
    }

    public function updateRememberToken(Authenticatable $user, $token) {}

    public function retrieveByCredentials(array $credentials)
    {
        return session('sso_user');
    }

    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        return true;
    }
}
