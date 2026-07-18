<?php

namespace App\DTOs;

final readonly class CreateAppointmentDTO
{
    public function __construct(
        public int $patientId,
        public int $doctorId,
        public int $scheduledBy,
        public string $scheduledAt,
        public int $durationMinutes,
        public ?string $reason,
        public ?string $notes,
        public string $status = 'scheduled',
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data, int $scheduledBy): self
    {
        return new self(
            patientId: (int) $data['patient_id'],
            doctorId: (int) $data['doctor_id'],
            scheduledBy: $scheduledBy,
            scheduledAt: $data['scheduled_at'],
            durationMinutes: (int) $data['duration_minutes'],
            reason: $data['reason'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'patient_id' => $this->patientId,
            'doctor_id' => $this->doctorId,
            'scheduled_by' => $this->scheduledBy,
            'scheduled_at' => $this->scheduledAt,
            'duration_minutes' => $this->durationMinutes,
            'reason' => $this->reason,
            'notes' => $this->notes,
            'status' => $this->status,
        ];
    }
}
