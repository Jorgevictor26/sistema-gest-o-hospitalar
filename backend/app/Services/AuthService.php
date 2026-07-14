<?php

namespace App\Services;

use App\DTOs\LoginDTO;
use App\DTOs\LoginResponseDTO;
use App\DTOs\RegisterDTO;
use App\DTOs\UserDTO;
use App\Models\Role;
use App\Exceptions\BlockedAccountException;
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

        if($user->is_active === false){
            throw new BlockedAccountException();
        }

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
        ]);

        $role = Role::where('name', $data->role)->firstOrFail();
        $user->roles()->attach($role->id);
        $user->is_active = true;
        $user->save();

        return UserDTO::fromModel($user);
    }
}
