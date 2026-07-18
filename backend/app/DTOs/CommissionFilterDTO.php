<?php

namespace App\DTOs;

class CommissionFilterDTO
{
    /** @param array<string, mixed> $values */
    public function __construct(public readonly array $values) {}

    public static function fromArray(array $data): self
    {
        return new self($data);
    }
}
