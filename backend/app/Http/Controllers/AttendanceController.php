<?php

namespace App\Http\Controllers;

use App\DTOs\AttendanceDTO;
use App\Http\Requests\DeleteAttendanceRequest;
use App\Http\Requests\StoreAttendanceRequest;
use App\Http\Requests\UpdateAttendanceRequest;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceService $attendanceService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'date_from' => ['sometimes', 'date'],
            'date_to' => ['sometimes', 'date', 'after_or_equal:date_from'],
            'doctor_id' => ['sometimes', 'integer', 'exists:doctors,id'],
            'speciality' => ['sometimes', 'nullable', 'string', 'max:255'],
            'patient_id' => ['sometimes', 'integer', 'exists:patients,id'],
            'payment_status' => ['sometimes', Rule::in(['paid', 'partial', 'unpaid'])],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ]);

        return AttendanceResource::collection(
            $this->attendanceService->list($filters, $request->user())
        );
    }

    public function store(StoreAttendanceRequest $request): JsonResponse
    {
        $attendance = $this->attendanceService->create(
            AttendanceDTO::fromArray($request->validated()),
            $request->user()->id,
        );

        return (new AttendanceResource($attendance))->response()->setStatusCode(201);
    }

    public function show(Request $request, Attendance $attendance): AttendanceResource
    {
        $this->ensureCanView($request, $attendance);

        $relations = ['patient', 'doctor.user', 'registeredBy', 'procedures'];

        if ($request->user()->hasAnyRole(['admin', 'receptionist'])) {
            $relations[] = 'payments.receiver';
            $relations[] = 'payments.voidedBy';
        }

        if ($request->user()->hasRole('admin')) {
            $relations[] = 'edits.editor';
        }

        return new AttendanceResource($attendance->load($relations));
    }

    public function update(UpdateAttendanceRequest $request, Attendance $attendance): AttendanceResource
    {
        return new AttendanceResource(
            $this->attendanceService->update(
                $attendance,
                AttendanceDTO::fromArray($request->validated()),
                $request->user()->id,
            )
        );
    }

    public function destroy(DeleteAttendanceRequest $request, Attendance $attendance): Response
    {
        $this->attendanceService->delete(
            $attendance,
            $request->user()->id,
            $request->validated('reason'),
        );

        return response()->noContent();
    }

    private function ensureCanView(Request $request, Attendance $attendance): void
    {
        $user = $request->user();

        if ($user->hasAnyRole(['admin', 'receptionist'])) {
            return;
        }

        abort_unless(
            $user->hasRole('doctor') && $attendance->doctor()->where('user_id', $user->id)->exists(),
            403,
            'Não tem permissão para consultar este atendimento.'
        );
    }
}
