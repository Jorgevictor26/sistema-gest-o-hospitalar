<?php

namespace App\Services;

use App\DTOs\LoginDTO;
use App\DTOs\LoginResponseDTO;
use App\DTOs\RegisterDTO;
use App\DTOs\UserDTO;
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

    public function register(RegisterDTO $data): UserDTO
    {
        $user = $this->users->create([
            'name' => $data->name,
            'email' => $data->email,
            'phone_number' => $data->phone_number,
            'password' => Hash::make($data->password),
            'role' => $data->role,
            'is_active' => true
        ]);

        return UserDTO::fromModel($user);
    }
}
