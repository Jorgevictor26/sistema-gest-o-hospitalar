<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class AppointmentService
{
    public function list(array $filters, User $user): LengthAwarePaginator
    {
        return Appointment::query()
            ->with(['patient', 'doctor.user', 'createdBy'])
            ->when($user->hasRole('doctor') && ! $user->hasAnyRole(['admin', 'receptionist']), fn ($query) => $query->whereHas('doctor', fn ($query) => $query->where('user_id', $user->id)))
            ->when($filters['date'] ?? null, fn ($query, $date) => $query->whereDate('scheduled_at', $date))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('scheduled_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('scheduled_at', '<=', $date))
            ->when($filters['doctor_id'] ?? null, fn ($query, $id) => $query->where('doctor_id', $id))
            ->when($filters['patient_id'] ?? null, fn ($query, $id) => $query->where('patient_id', $id))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderBy('scheduled_at')
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();
    }

    public function create(array $data, User $creator): Appointment
    {
        $this->ensureSlotAvailable($data['doctor_id'], $data['scheduled_at']);

        return Appointment::create([...$data, 'created_by' => $creator->id])
            ->load(['patient', 'doctor.user', 'createdBy']);
    }

    public function update(Appointment $appointment, array $data): Appointment
    {
        if (isset($data['doctor_id']) || isset($data['scheduled_at'])) {
            $this->ensureSlotAvailable(
                $data['doctor_id'] ?? $appointment->doctor_id,
                $data['scheduled_at'] ?? $appointment->scheduled_at,
                $appointment->id,
            );
        }

        $appointment->update($data);

        return $appointment->refresh()->load(['patient', 'doctor.user', 'createdBy']);
    }

    private function ensureSlotAvailable(int $doctorId, mixed $scheduledAt, ?int $ignoreId = null): void
    {
        $exists = Appointment::query()
            ->where('doctor_id', $doctorId)
            ->where('scheduled_at', $scheduledAt)
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();

        if ($exists) {
            throw new ConflictHttpException('O médico já possui uma marcação neste horário.');
        }
    }
}
