<?php

use App\DTOs\Auth\LoginDTO;
use App\Repositories\UserRepository;

class AuthService
{
    public function __construct(
        public readonly UserRepository $userRepository,
    ) {}

    public function login(LoginDTO $data) {
        
    }
}
