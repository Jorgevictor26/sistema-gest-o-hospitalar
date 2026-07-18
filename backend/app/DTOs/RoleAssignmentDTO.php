<?php

namespace App\DTOs;

class RoleAssignmentDTO
{
    /** @param array<int, string> $roles */
    public function __construct(public readonly array $roles) {}

    public static function fromArray(array $data): self
    {
        return new self($data['roles']);
    }
}
