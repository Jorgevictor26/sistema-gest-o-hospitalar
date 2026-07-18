<?php

namespace App\Services;

use App\DTOs\LoginDTO;
use App\DTOs\LoginResponseDTO;
use App\DTOs\RegisterDTO;
use App\DTOs\UserDTO;
use App\Exceptions\BlockedAccountException;
use App\Exceptions\InvalidCredentialsException;
use App\Models\Role;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function __construct(
        public readonly UserRepository $users,
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

    public function register(RegisterDTO $data): UserDTO
    {
        $user = DB::transaction(function () use ($data) {
            $user = $this->users->create([
                'name' => $data->name,
                'email' => $data->email,
                'phone_number' => $data->phone_number,
                'password' => Hash::make($data->password),
                'is_active' => true,
            ]);

            $roleIds = Role::query()
                ->whereIn('name', $data->roles)
                ->pluck('id');

            $user->roles()->sync($roleIds);

            return $user;
        });

        return UserDTO::fromModel($user);
    }
}
