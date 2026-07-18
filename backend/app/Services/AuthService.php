<?php

namespace App\Services;

use App\DTOs\LoginDTO;
use App\DTOs\LoginResponseDTO;
use App\Exceptions\BlockedAccountException;
use App\Exceptions\InvalidCredentialsException;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function __construct(
        private readonly UserRepository $users,
    ) {}

    public function login(LoginDTO $data): LoginResponseDTO
    {
        $user = $this->users->findByEmail($data->email);

        if (! $user || ! Hash::check($data->password, $user->password)) {
            throw new InvalidCredentialsException;
        }

        if ($user->is_active === false) {
            throw new BlockedAccountException;
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return LoginResponseDTO::fromModel($user, $token);
    }
}
