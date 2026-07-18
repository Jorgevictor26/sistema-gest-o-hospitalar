<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Attendance;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Procedure;
use App\Models\User;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function __construct(
        private readonly AttendanceReportService $reportService,
        private readonly DailyClosureService $closureService,
        private readonly DoctorAreaService $doctorAreaService,
    ) {}

    /** @return array<string, mixed> */
    public function data(array $filters, User $user): array
    {
        if ($user->hasRole('doctor') && ! $user->hasAnyRole(['admin', 'receptionist'])) {
            return $this->doctorData($user);
        }

        $report = $this->reportService->generate($filters);
        [$start, $end] = $this->reportService->resolvePeriod($filters);
        $closure = $start->isSameDay($end)
            ? $this->closureService->activeForDate($start)
            : null;

        $appointments = Appointment::query()
            ->with(['patient', 'doctor.user'])
            ->whereBetween('scheduled_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()]);

        $appointmentStatus = (clone $appointments)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $recentAppointments = (clone $appointments)
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->orderBy('scheduled_at')
            ->limit(10)
            ->get();

        $procedureQuery = Procedure::query()
            ->join('attendance_procedure', 'attendance_procedure.procedure_id', '=', 'procedures.id')
            ->join('attendances', 'attendances.id', '=', 'attendance_procedure.attendance_id')
            ->whereNull('attendances.deleted_at')
            ->whereBetween('attendances.attendance_date', [$start->toDateString(), $end->toDateString()]);

        $procedures = $procedureQuery
            ->groupBy('procedures.id', 'procedures.procedure')
            ->orderByDesc('total_performed')
            ->get([
                'procedures.id',
                'procedures.procedure',
                DB::raw('COUNT(*) as total_performed'),
                DB::raw('SUM(attendance_procedure.price) as generated_value'),
            ]);

        $data = [
            'scope' => 'general',
            'report' => $report,
            'appointments' => [
                'total' => (int) $appointmentStatus->sum(),
                'by_status' => $appointmentStatus,
                'upcoming' => $recentAppointments,
            ],
            'procedures' => $procedures,
            'day_closure' => [
                'is_closed' => $closure !== null,
                'id' => $closure?->id,
                'closed_at' => $closure?->closed_at,
                'closed_by' => $closure ? [
                    'id' => $closure->closedBy->id,
                    'name' => $closure->closedBy->name,
                ] : null,
            ],
        ];

        $data['general'] = [
            'active_doctors' => Doctor::whereHas('user', fn (Builder $query) => $query->where('is_active', true))->count(),
            'total_patients' => Patient::count(),
            'total_procedures' => Procedure::count(),
            'today_attendances' => Attendance::whereDate('attendance_date', today())->count(),
        ];

        return $data;
    }

    /** @return array<string, mixed> */
    private function doctorData(User $user): array
    {
        $data = $this->doctorAreaService->dashboard($user);
        $doctor = $data['doctor'];

        return [
            'scope' => 'doctor',
            'doctor' => [
                'id' => $doctor->id,
                'name' => $doctor->user->name,
                'speciality' => $doctor->speciality,
                'professional_number' => $doctor->professional_number,
                'commission_percentage' => $doctor->commission_percentage,
            ],
            'statistics' => $data['metrics'],
            'recent_attendances' => $data['recent']->map(fn (Attendance $attendance): array => [
                'id' => $attendance->id,
                'attendance_date' => $attendance->attendance_date->format('Y-m-d'),
                'patient' => ['id' => $attendance->patient->id, 'name' => $attendance->patient->name],
                'total_amount' => $attendance->total_amount,
                'commission_percentage' => $attendance->commission_percentage,
                'commission_amount' => Money::format($attendance->commissionAmount()),
            ]),
            'daily_patient_flow' => $data['dailyFlow'],
            'top_procedures_this_month' => $data['topProcedures'],
        ];
    }
}
