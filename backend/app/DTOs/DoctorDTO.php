<?php

namespace App\DTOs;

class DoctorDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $phone_number,
        public readonly string $speciality,
        public readonly ?string $professional_number,
        public readonly string $password,
        public readonly string $commission_percentage,
        public readonly bool $is_available,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            phone_number: $data['phone_number'],
            speciality: $data['speciality'],
            professional_number: $data['professional_number'] ?? null,
            password: $data['password'],
            commission_percentage: (string) $data['commission_percentage'],
            is_available: (bool) ($data['is_available'] ?? true),
        );
    }
}
