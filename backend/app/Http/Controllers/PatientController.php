<?php

namespace App\Http\Controllers;

use App\DTOs\PatientDTO;
use App\Http\Requests\ChangePatientStatusRequest;
use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Http\Resources\AttendanceResource;
use App\Http\Resources\PatientResource;
use App\Models\Patient;
use App\Services\PatientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PatientController extends Controller
{
    public function __construct(private readonly PatientService $patientService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
            'active' => ['sometimes', 'boolean'],
        ]);

        return PatientResource::collection(
            $this->patientService->list(
                $filters['search'] ?? null,
                array_key_exists('active', $filters)
                    ? $request->boolean('active')
                    : ($request->user()->hasRole('admin') ? null : true),
                $filters['per_page'] ?? 15,
            )
        );
    }

    public function store(StorePatientRequest $request): JsonResponse
    {
        return (new PatientResource($this->patientService->create(PatientDTO::fromArray($request->validated()))))
            ->response()->setStatusCode(201);
    }

    public function show(Patient $patient): PatientResource
    {
        return new PatientResource($patient->loadCount('attendances'));
    }

    public function update(UpdatePatientRequest $request, Patient $patient): PatientResource
    {
        return new PatientResource(
            $this->patientService->update($patient, PatientDTO::fromArray($request->validated()))
        );
    }

    public function changeStatus(ChangePatientStatusRequest $request, Patient $patient): PatientResource
    {
        return new PatientResource(
            $this->patientService->changeStatus($patient, $request->boolean('is_active'))
        );
    }

    public function history(Request $request, Patient $patient): AnonymousResourceCollection
    {
        $filters = $request->validate(['per_page' => ['sometimes', 'integer', 'between:1,100']]);

        return AttendanceResource::collection(
            $this->patientService->history($patient, $filters['per_page'] ?? 15)
        );
    }
}
