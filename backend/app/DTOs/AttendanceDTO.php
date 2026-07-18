<?php

namespace App\DTOs;

class AttendanceDTO
{
    /** @param array<string, mixed> $values */
    public function __construct(public readonly array $values) {}

    public static function fromArray(array $data): self
    {
        return new self(array_intersect_key($data, array_flip([
            'patient_id', 'doctor_id', 'attendance_date', 'procedures',
            'amount_paid', 'payment_method', 'payment_reference', 'reason',
        ])));
    }
}
