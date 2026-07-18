<?php

namespace App\Services;

use App\DTOs\CancelAppointmentDTO;
use App\DTOs\ChangeAppointmentStatusDTO;
use App\DTOs\CreateAppointmentDTO;
use App\DTOs\RescheduleAppointmentDTO;
use App\Models\Appointment;
use App\Repositories\AppointmentRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class AppointmentService
{
    public function __construct(private readonly AppointmentRepository $appointments) {}

    public function create(CreateAppointmentDTO $data): Appointment
    {
        return DB::transaction(function () use ($data): Appointment {
            $this->ensureSlotAvailable(
                $data->doctorId,
                Carbon::parse($data->scheduledAt),
                $data->durationMinutes,
            );

            return $this->loadRelations($this->appointments->create($data));
        });
    }

    public function reschedule(Appointment $appointment, RescheduleAppointmentDTO $data): Appointment
    {
        return DB::transaction(function () use ($appointment, $data): Appointment {
            $appointment = $this->appointments->findForUpdate($appointment->id);

            if (! in_array($appointment->status, Appointment::RESCHEDULABLE_STATUSES, true)) {
                throw new ConflictHttpException('Esta marcação não pode ser reagendada no estado atual.');
            }

            $doctorId = $data->doctorId ?? $appointment->doctor_id;

            if (! $this->appointments->doctorIsActive($doctorId)) {
                throw new ConflictHttpException('O médico selecionado não está ativo.');
            }

            $this->ensureSlotAvailable(
                $doctorId,
                Carbon::parse($data->scheduledAt),
                $data->durationMinutes,
                $appointment->id,
            );

            return $this->loadRelations($this->appointments->reschedule($appointment, $data));
        });
    }

    public function cancel(Appointment $appointment, CancelAppointmentDTO $data): Appointment
    {
        return DB::transaction(function () use ($appointment, $data): Appointment {
            $appointment = $this->appointments->findForUpdate($appointment->id);
            $this->ensureTransitionIsAllowed($appointment, Appointment::STATUS_CANCELLED);

            return $this->loadRelations($this->appointments->update($appointment, [
                'status' => Appointment::STATUS_CANCELLED,
                'cancelled_by' => $data->cancelledBy,
                'cancelled_at' => now(),
                'cancellation_reason' => $data->reason,
            ]));
        });
    }

    public function changeStatus(Appointment $appointment, ChangeAppointmentStatusDTO $data): Appointment
    {
        return DB::transaction(function () use ($appointment, $data): Appointment {
            $appointment = $this->appointments->findForUpdate($appointment->id);
            $this->ensureTransitionIsAllowed($appointment, $data->status);

            return $this->loadRelations($this->appointments->update($appointment, [
                'status' => $data->status,
            ]));
        });
    }

    private function ensureSlotAvailable(
        int $doctorId,
        Carbon $startsAt,
        int $durationMinutes,
        ?int $ignoreAppointmentId = null,
    ): void {
        if ($this->appointments->hasScheduleConflict($doctorId, $startsAt, $durationMinutes, $ignoreAppointmentId)) {
            throw new ConflictHttpException('O médico já possui uma marcação neste horário.');
        }
    }

    private function ensureTransitionIsAllowed(Appointment $appointment, string $status): void
    {
        if (! $appointment->canTransitionTo($status)) {
            throw new ConflictHttpException('Transição de estado inválida para esta marcação.');
        }
    }

    private function loadRelations(Appointment $appointment): Appointment
    {
        return $appointment->load(['patient', 'doctor.user', 'scheduledBy', 'cancelledBy']);
    }
}
