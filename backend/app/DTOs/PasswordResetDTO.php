<?php

namespace App\DTOs;

class PasswordResetDTO
{
    public function __construct(public readonly string $password) {}

    public static function fromArray(array $data): self
    {
        return new self($data['password']);
    }
}
