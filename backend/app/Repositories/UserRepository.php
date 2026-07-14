<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    public function findById(int $id): User
    {
        return User::findOrFail($id);
    }
    public function findByEmail(string $email): ?User
    {
        return User::Where('email', $email)->first();
    }
    public function create( array $data): User
    {
        return User::create($data);
    }

}
