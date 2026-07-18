<?php

namespace App\DTOs;

class PaymentDTO
{
    /** @param array<string, mixed> $values */
    public function __construct(public readonly array $values) {}

    public static function fromArray(array $data): self
    {
        return new self(array_intersect_key($data, array_flip([
            'amount', 'method', 'reference', 'notes', 'paid_at',
        ])));
    }
}
