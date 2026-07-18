<?php

namespace App\DTOs;

class UpdateUserDTO
{
    /** @param array<string, mixed> $values */
    public function __construct(public readonly array $values) {}

    public static function fromArray(array $data): self
    {
        return new self(array_intersect_key($data, array_flip(['name', 'email', 'phone_number'])));
    }

    public function toArray(): array
    {
        return $this->values;
    }
}
