<?php

use App\DTOs\Auth\LoginDTO;
use App\Exceptions\InvalidCredentialsException;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;
use Nette\Schema\ValidationException;


class AuthService
{
    public function __construct(
        public readonly UserRepository $userRepository,
    ) {}

    public function login(LoginDTO $data) {
        $user = $this->userRepository->findByEmail($data->email);

        if (!$user || !Hash::check($data->password, $user->password)){
            throw new InvalidCredentialsException();
        }
        return $user;
    }
}
