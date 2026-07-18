<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceReportRequest;
use App\Services\AttendanceReportService;
use Illuminate\Http\JsonResponse;

class AttendanceReportController extends Controller
{
    public function __construct(
        private readonly AttendanceReportService $reportService,
    ) {}

    public function __invoke(AttendanceReportRequest $request): JsonResponse
    {
        return response()->json(
            $this->reportService->generate($request->validated())
        );
    }
}
