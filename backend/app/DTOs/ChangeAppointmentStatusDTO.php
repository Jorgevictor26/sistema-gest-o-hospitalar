<?php

namespace App\DTOs;

final readonly class ChangeAppointmentStatusDTO
{
    public function __construct(public string $status) {}
}
