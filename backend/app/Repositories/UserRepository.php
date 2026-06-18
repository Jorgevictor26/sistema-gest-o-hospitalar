<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    public function findById(int $id): User
    {
        return User::findById($id);
    }
    public function findByEmail(string $email): User
    {
        return User::Where('email', $email)->first();
    }
}
