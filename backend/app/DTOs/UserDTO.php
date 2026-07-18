<?php

namespace App\DTOs;

use App\Models\User;

class UserDTO
{
    /**
     * @param  array<int, string>  $roles
     */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $phone_number,
        public readonly bool $is_active,
        public readonly array $roles,
    ) {}

    public static function fromModel(User $user): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            phone_number: $user->phone_number,
            is_active: $user->is_active,
            roles: $user->roles()->pluck('name')->all(),
        );
    }
}
