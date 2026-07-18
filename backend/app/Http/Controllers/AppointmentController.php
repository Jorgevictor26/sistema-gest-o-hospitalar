<?php

namespace App\Http\Controllers;

use App\DTOs\CancelAppointmentDTO;
use App\DTOs\ChangeAppointmentStatusDTO;
use App\DTOs\CreateAppointmentDTO;
use App\DTOs\RescheduleAppointmentDTO;
use App\Http\Requests\CancelAppointmentRequest;
use App\Http\Requests\ChangeAppointmentStatusRequest;
use App\Http\Requests\RescheduleAppointmentRequest;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Services\AppointmentService;
use Illuminate\Http\JsonResponse;

class AppointmentController extends Controller
{
    public function __construct(private readonly AppointmentService $appointmentService) {}

    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        return (new AppointmentResource(
            $this->appointmentService->create(
                CreateAppointmentDTO::fromArray($request->validated(), $request->user()->id)
            )
        ))->response()->setStatusCode(201);
    }

    public function reschedule(RescheduleAppointmentRequest $request, Appointment $appointment): AppointmentResource
    {
        return new AppointmentResource(
            $this->appointmentService->reschedule(
                $appointment,
                RescheduleAppointmentDTO::fromArray($request->validated()),
            )
        );
    }

    public function cancel(CancelAppointmentRequest $request, Appointment $appointment): AppointmentResource
    {
        return new AppointmentResource(
            $this->appointmentService->cancel(
                $appointment,
                new CancelAppointmentDTO(
                    cancelledBy: $request->user()->id,
                    reason: $request->validated('cancellation_reason'),
                ),
            )
        );
    }

    public function confirm(ChangeAppointmentStatusRequest $request, Appointment $appointment): AppointmentResource
    {
        return $this->changeStatus($appointment, Appointment::STATUS_CONFIRMED);
    }

    public function complete(ChangeAppointmentStatusRequest $request, Appointment $appointment): AppointmentResource
    {
        return $this->changeStatus($appointment, Appointment::STATUS_COMPLETED);
    }

    public function markAsNoShow(ChangeAppointmentStatusRequest $request, Appointment $appointment): AppointmentResource
    {
        return $this->changeStatus($appointment, Appointment::STATUS_NO_SHOW);
    }

    private function changeStatus(Appointment $appointment, string $status): AppointmentResource
    {
        return new AppointmentResource(
            $this->appointmentService->changeStatus($appointment, new ChangeAppointmentStatusDTO($status))
        );
    }
}
