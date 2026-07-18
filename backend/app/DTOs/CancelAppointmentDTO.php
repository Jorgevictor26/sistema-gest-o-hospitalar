<?php

namespace App\DTOs;

final readonly class CancelAppointmentDTO
{
    public function __construct(
        public int $cancelledBy,
        public string $reason,
    ) {}
}
