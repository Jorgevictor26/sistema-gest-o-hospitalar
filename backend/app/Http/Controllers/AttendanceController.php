<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttendanceRequest;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceService $attendanceService,
    ) {}

    public function store(StoreAttendanceRequest $request): JsonResponse
    {
        $attendance = $this->attendanceService->create(
            $request->validated(),
            $request->user()->id,
        );

        return response()->json($attendance, 201);
    }
}
