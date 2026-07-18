<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Services\AppointmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class AppointmentController extends Controller
{
    public function __construct(private readonly AppointmentService $appointmentService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'date' => ['sometimes', 'date'],
            'date_from' => ['sometimes', 'date'],
            'date_to' => ['sometimes', 'date', 'after_or_equal:date_from'],
            'doctor_id' => ['sometimes', 'integer', 'exists:doctors,id'],
            'patient_id' => ['sometimes', 'integer', 'exists:patients,id'],
            'status' => ['sometimes', Rule::in(['scheduled', 'confirmed', 'completed', 'cancelled', 'no_show'])],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ]);

        return AppointmentResource::collection($this->appointmentService->list($filters, $request->user()));
    }

    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        return (new AppointmentResource(
            $this->appointmentService->create($request->validated(), $request->user())
        ))->response()->setStatusCode(201);
    }

    public function update(UpdateAppointmentRequest $request, Appointment $appointment): AppointmentResource
    {
        return new AppointmentResource(
            $this->appointmentService->update($appointment, $request->validated())
        );
    }
}
