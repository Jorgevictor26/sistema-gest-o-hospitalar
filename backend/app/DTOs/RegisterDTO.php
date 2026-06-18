<?php

namespace App\DTOs;


class RegisterDTO
{

    public function  __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $phone_number,
        public readonly string $role,
        public readonly string $password
    ) {}

    public static function fromArray(array $data): self {
        return new self(
            name: $data['name'],
            email: $data['email'],
            phone_number: $data['phone_number'],
            password: $data['password'],
            role: $data['role']
        );
    }
}
