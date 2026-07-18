<?php

namespace App\DTOs;

final readonly class RescheduleAppointmentDTO
{
    public function __construct(
        public string $scheduledAt,
        public int $durationMinutes,
        public ?int $doctorId,
        public ?string $reason,
        public ?string $notes,
        public bool $reasonWasProvided,
        public bool $notesWereProvided,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            scheduledAt: $data['scheduled_at'],
            durationMinutes: (int) $data['duration_minutes'],
            doctorId: isset($data['doctor_id']) ? (int) $data['doctor_id'] : null,
            reason: $data['reason'] ?? null,
            notes: $data['notes'] ?? null,
            reasonWasProvided: array_key_exists('reason', $data),
            notesWereProvided: array_key_exists('notes', $data),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array_filter([
            'scheduled_at' => $this->scheduledAt,
            'duration_minutes' => $this->durationMinutes,
            'doctor_id' => $this->doctorId,
            'reason' => $this->reason,
            'notes' => $this->notes,
        ], fn (mixed $value, string $key): bool => match ($key) {
            'reason' => $this->reasonWasProvided,
            'notes' => $this->notesWereProvided,
            default => $value !== null,
        }, ARRAY_FILTER_USE_BOTH);
    }
}
