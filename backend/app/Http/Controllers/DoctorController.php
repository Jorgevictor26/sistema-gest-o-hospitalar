<?php

namespace App\Http\Controllers;

use App\DTOs\DoctorDTO;
use App\DTOs\UpdateDoctorDTO;
use App\Http\Requests\ChangeDoctorStatusRequest;
use App\Http\Requests\DoctorRequest;
use App\Http\Requests\UpdateDoctorCommissionRequest;
use App\Http\Requests\UpdateDoctorRequest;
use App\Http\Resources\DoctorResource;
use App\Models\Doctor;
use App\Services\DoctorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DoctorController extends Controller
{
    public function __construct(
        private readonly DoctorService $doctorService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'active' => ['sometimes', 'boolean'],
            'speciality' => ['sometimes', 'nullable', 'string', 'max:255'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ]);

        $doctors = $this->doctorService->list(
            $filters['search'] ?? null,
            $filters['speciality'] ?? null,
            array_key_exists('active', $filters)
                ? $request->boolean('active')
                : ($request->user()->hasRole('admin') ? null : true),
            $filters['per_page'] ?? 15,
        );

        return DoctorResource::collection($doctors);
    }

    public function update(UpdateDoctorRequest $request, Doctor $doctor): DoctorResource
    {
        return new DoctorResource(
            $this->doctorService->update($doctor->load('user'), UpdateDoctorDTO::fromArray($request->validated()))
        );
    }

    public function changeStatus(ChangeDoctorStatusRequest $request, Doctor $doctor): DoctorResource
    {
        return new DoctorResource(
            $this->doctorService->changeStatus($doctor->load('user'), $request->boolean('is_active'), $request->user())
        );
    }

    public function statistics(Doctor $doctor): JsonResponse
    {
        return response()->json($this->doctorService->statistics($doctor));
    }

    public function registerDoctor(DoctorRequest $request): JsonResponse
    {
        $doctor = $this->doctorService->create(DoctorDTO::fromArray($request->validated()));

        return (new DoctorResource($doctor))->response()->setStatusCode(201);
    }

    public function show(Doctor $doctor): DoctorResource
    {
        return new DoctorResource($doctor->load('user.roles'));
    }

    public function updateCommission(UpdateDoctorCommissionRequest $request, Doctor $doctor): DoctorResource
    {
        return new DoctorResource(
            $this->doctorService->updateCommission(
                $doctor,
                (string) $request->validated('commission_percentage'),
            )
        );
    }
}
