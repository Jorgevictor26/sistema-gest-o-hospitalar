<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceReportRequest;
use App\Services\AttendanceExportService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceExportController extends Controller
{
    public function __construct(
        private readonly AttendanceExportService $exportService,
    ) {}

    public function csv(AttendanceReportRequest $request): StreamedResponse
    {
        return $this->exportService->csv($request->validated(), $request->user());
    }

    public function pdf(AttendanceReportRequest $request): Response
    {
        $filters = $request->validated();

        return $this->exportService
            ->pdf($filters, $request->user())
            ->download($this->exportService->filename($filters, 'pdf'));
    }
}
