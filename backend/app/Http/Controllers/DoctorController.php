<?php

namespace App\Http\Controllers;

use App\DTOs\DoctorDTO;
use App\Http\Requests\DoctorRequest;
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
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ]);

        $doctors = $this->doctorService->list(
            $filters['search'] ?? null,
            array_key_exists('active', $filters) ? $request->boolean('active') : true,
            $filters['per_page'] ?? 15,
        );

        return DoctorResource::collection($doctors);
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
}
