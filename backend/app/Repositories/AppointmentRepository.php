<?php

namespace App\Repositories;

use App\DTOs\CreateAppointmentDTO;
use App\DTOs\RescheduleAppointmentDTO;
use App\Models\Appointment;
use App\Models\Doctor;
use Carbon\CarbonInterface;

class AppointmentRepository
{
    public function create(CreateAppointmentDTO $data): Appointment
    {
        return Appointment::create($data->toArray());
    }

    public function findForUpdate(int $id): Appointment
    {
        return Appointment::query()->lockForUpdate()->findOrFail($id);
    }

    public function hasScheduleConflict(
        int $doctorId,
        CarbonInterface $startsAt,
        int $durationMinutes,
        ?int $ignoreAppointmentId = null,
    ): bool {
        $endsAt = $startsAt->copy()->addMinutes($durationMinutes);

        return Appointment::query()
            ->where('doctor_id', $doctorId)
            ->whereNotIn('status', [Appointment::STATUS_CANCELLED, Appointment::STATUS_NO_SHOW])
            ->where('scheduled_at', '<', $endsAt)
            ->when($ignoreAppointmentId, fn ($query) => $query->whereKeyNot($ignoreAppointmentId))
            ->lockForUpdate()
            ->get(['scheduled_at', 'duration_minutes'])
            ->contains(fn (Appointment $appointment) => $appointment->scheduled_at
                ->copy()->addMinutes($appointment->duration_minutes)->greaterThan($startsAt));
    }

    public function doctorIsActive(int $doctorId): bool
    {
        return Doctor::query()
            ->whereKey($doctorId)
            ->where('is_available', true)
            ->whereHas('user', fn ($query) => $query->where('is_active', true))
            ->exists();
    }

    public function reschedule(Appointment $appointment, RescheduleAppointmentDTO $data): Appointment
    {
        $appointment->update($data->toArray());

        return $appointment->refresh();
    }

    /** @param array<string, mixed> $data */
    public function update(Appointment $appointment, array $data): Appointment
    {
        $appointment->update($data);

        return $appointment->refresh();
    }
}
