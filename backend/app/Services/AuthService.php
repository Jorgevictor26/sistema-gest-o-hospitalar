<?php

namespace App\Services;

use App\DTOs\Auth\LoginDTO;
use App\Dtos\LoginResponseDTO;
use App\Repositories\UserRepository;
use App\Exceptions\InvalidCredentialsException;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function __construct(
        public readonly UserRepository $users,
    ) {}

    public function login(LoginDTO $data): LoginResponseDTO
    {
        $user = $this->users->findByEmail($data->email);

        if (!$user || !Hash::check($data->password, $user->password)) {
            throw new InvalidCredentialsException();
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return LoginResponseDTO::fromModel($user, $token);
    }
}
