<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\User;
use App\Support\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceExportService
{
    public function __construct(
        private readonly AttendanceReportService $reportService,
    ) {}

    public function csv(array $filters, User $user): StreamedResponse
    {
        $attendances = $this->attendances($filters);
        $filename = $this->filename($filters, 'csv');

        return response()->streamDownload(function () use ($attendances): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'Data',
                'Paciente',
                'Médico',
                'Especialidade',
                'Procedimentos',
                'Total cobrado',
                'Total pago',
                'Saldo pendente',
                'Estado do pagamento',
                'Registado por',
            ], ';');

            foreach ($attendances as $attendance) {
                fputcsv($output, [
                    $attendance->attendance_date->format('Y-m-d'),
                    $attendance->patient->name,
                    $attendance->doctor->user->name,
                    $attendance->doctor->speciality,
                    $attendance->procedures->map(
                        fn ($procedure): string => "{$procedure->procedure} ({$procedure->pivot->price})"
                    )->implode(' | '),
                    $attendance->total_amount,
                    $attendance->amount_paid,
                    Money::format($attendance->pendingAmount()),
                    $this->translatedPaymentStatus($attendance),
                    $attendance->registeredBy->name,
                ], ';');
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function pdf(array $filters, User $user): \Barryvdh\DomPDF\PDF
    {
        $report = $this->reportService->generate($filters);
        $attendances = $this->attendances($filters);

        return Pdf::loadView('reports.attendances', [
            'report' => $report,
            'attendances' => $attendances,
            'generatedBy' => $user,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');
    }

    public function filename(array $filters, string $extension): string
    {
        [$start, $end] = $this->reportService->resolvePeriod($filters);

        return "relatorio-atendimentos-{$start->format('Y-m-d')}-{$end->format('Y-m-d')}.{$extension}";
    }

    /**
     * @return Collection<int, Attendance>
     */
    private function attendances(array $filters): Collection
    {
        return $this->reportService->attendanceQuery($filters)
            ->with(['patient', 'doctor.user', 'procedures', 'registeredBy'])
            ->orderBy('attendance_date')
            ->orderBy('id')
            ->get();
    }

    private function translatedPaymentStatus(Attendance $attendance): string
    {
        return match ($attendance->paymentStatus()) {
            'unpaid' => 'Não pago',
            'partial' => 'Parcial',
            'paid' => 'Pago',
        };
    }
}
