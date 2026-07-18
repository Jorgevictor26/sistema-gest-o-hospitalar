<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceReportRequest;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService) {}

    public function __invoke(AttendanceReportRequest $request): JsonResponse
    {
        return response()->json(
            $this->dashboardService->data($request->validated(), $request->user())
        );
    }
}
