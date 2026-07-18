<?php

namespace App\Http\Controllers;

use App\DTOs\CommissionFilterDTO;
use App\DTOs\DoctorProfileDTO;
use App\Http\Requests\ChangeOwnPasswordRequest;
use App\Http\Requests\DoctorCommissionRequest;
use App\Http\Requests\UpdateOwnDoctorProfileRequest;
use App\Http\Resources\AttendanceResource;
use App\Http\Resources\DoctorCommissionResource;
use App\Http\Resources\DoctorPatientResource;
use App\Http\Resources\DoctorProfileResource;
use App\Models\Patient;
use App\Services\DoctorAreaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DoctorAreaController extends Controller
{
    public function __construct(private readonly DoctorAreaService $service) {}

    public function profile(Request $request): DoctorProfileResource
    {
        return new DoctorProfileResource($this->service->profile($request->user()));
    }

    public function updateProfile(UpdateOwnDoctorProfileRequest $request): DoctorProfileResource
    {
        return new DoctorProfileResource($this->service->updateProfile(
            $request->user(), DoctorProfileDTO::fromArray($request->validated())
        ));
    }

    public function changePassword(ChangeOwnPasswordRequest $request): JsonResponse
    {
        $this->service->changePassword($request->user(), $request->validated('password'));

        return response()->json(['message' => 'Palavra-passe alterada. Inicie sessão novamente.']);
    }

    public function commissions(DoctorCommissionRequest $request): JsonResponse
    {
        $result = $this->service->commissions($request->user(), CommissionFilterDTO::fromArray($request->validated()));

        return response()->json([
            'period' => $result['period'],
            'summary' => $result['summary'],
            'attendances' => DoctorCommissionResource::collection($result['attendances'])->response()->getData(true),
        ]);
    }

    public function patients(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ]);
        $patients = $this->service->patients($request->user(), $filters['search'] ?? null, $filters['per_page'] ?? 15);

        return DoctorPatientResource::collection($patients);
    }

    public function patientHistory(Request $request, Patient $patient): AnonymousResourceCollection
    {
        $filters = $request->validate(['per_page' => ['sometimes', 'integer', 'between:1,100']]);
        $attendances = $this->service->patientHistory($request->user(), $patient, $filters['per_page'] ?? 15);

        return AttendanceResource::collection($attendances);
    }
}
