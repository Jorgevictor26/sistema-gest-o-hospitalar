<?php

namespace App\DTOs;

use App\DTOs\UserDTO;
use App\Models\User;

class LoginResponseDTO
{

    public function __construct(
        public readonly UserDTO $user,
        public readonly string $token
    ) {}

    public static function fromModel(User $user, string $token): self
    {
        return new self(
            user : UserDTO::fromModel($user),
            token : $token
        );
    }
}
