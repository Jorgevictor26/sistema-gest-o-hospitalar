<?php

namespace App\DTOs;

class PatientDTO
{
    /** @param array<string, mixed> $values */
    public function __construct(public readonly array $values) {}

    public static function fromArray(array $data): self
    {
        return new self(array_intersect_key($data, array_flip([
            'name', 'phone_number', 'gender', 'date_of_birth',
            'identity_card', 'email', 'address',
        ])));
    }
}
