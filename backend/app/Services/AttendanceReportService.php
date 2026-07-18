<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Payment;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AttendanceReportService
{
    /**
     * @return array<string, mixed>
     */
    public function generate(array $filters): array
    {
        [$start, $end, $period] = $this->resolvePeriod($filters);
        $attendances = $this->attendanceQuery($filters);

        $summary = (clone $attendances)
            ->selectRaw('COUNT(*) as total_attendances')
            ->selectRaw('COUNT(DISTINCT patient_id) as unique_patients')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_charged')
            ->selectRaw('COALESCE(SUM(amount_paid), 0) as received_for_attendances')
            ->selectRaw('COALESCE(SUM(total_amount - amount_paid), 0) as total_pending')
            ->first();

        $paymentStatus = (clone $attendances)
            ->selectRaw('SUM(CASE WHEN amount_paid <= 0 THEN 1 ELSE 0 END) as unpaid')
            ->selectRaw('SUM(CASE WHEN amount_paid > 0 AND amount_paid < total_amount THEN 1 ELSE 0 END) as partial')
            ->selectRaw('SUM(CASE WHEN amount_paid >= total_amount THEN 1 ELSE 0 END) as paid')
            ->first();

        $byDoctor = (clone $attendances)
            ->join('doctors', 'doctors.id', '=', 'attendances.doctor_id')
            ->join('users', 'users.id', '=', 'doctors.user_id')
            ->groupBy('doctors.id', 'users.name', 'doctors.speciality')
            ->orderBy('users.name')
            ->get([
                'doctors.id as doctor_id',
                'users.name as doctor',
                'doctors.speciality',
                DB::raw('COUNT(attendances.id) as total_attendances'),
                DB::raw('COALESCE(SUM(attendances.total_amount), 0) as total_charged'),
                DB::raw('COALESCE(SUM(attendances.amount_paid), 0) as total_received'),
                DB::raw('COALESCE(SUM(attendances.total_amount - attendances.amount_paid), 0) as total_pending'),
            ]);

        $bySpeciality = (clone $attendances)
            ->join('doctors', 'doctors.id', '=', 'attendances.doctor_id')
            ->groupBy('doctors.speciality')
            ->orderBy('doctors.speciality')
            ->get([
                'doctors.speciality',
                DB::raw('COUNT(attendances.id) as total_attendances'),
                DB::raw('COALESCE(SUM(attendances.total_amount), 0) as total_charged'),
                DB::raw('COALESCE(SUM(attendances.amount_paid), 0) as total_received'),
                DB::raw('COALESCE(SUM(attendances.total_amount - attendances.amount_paid), 0) as total_pending'),
            ]);

        $byProcedure = (clone $attendances)
            ->join('attendance_procedure', 'attendance_procedure.attendance_id', '=', 'attendances.id')
            ->join('procedures', 'procedures.id', '=', 'attendance_procedure.procedure_id')
            ->groupBy('procedures.id', 'procedures.procedure')
            ->orderBy('procedures.procedure')
            ->get([
                'procedures.id as procedure_id',
                'procedures.procedure',
                DB::raw('COUNT(*) as usage_count'),
                DB::raw('COALESCE(SUM(attendance_procedure.price), 0) as total_charged'),
            ]);

        $payments = Payment::query()
            ->whereNull('voided_at')
            ->whereBetween('paid_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()]);

        if (isset($filters['doctor_id'])) {
            $payments->whereHas('attendance', fn (Builder $query) => $query->where('doctor_id', $filters['doctor_id']));
        }

        if (isset($filters['patient_id'])) {
            $payments->whereHas('attendance', fn (Builder $query) => $query->where('patient_id', $filters['patient_id']));
        }

        $cashReceived = (clone $payments)->sum('amount');
        $paymentsByMethod = (clone $payments)
            ->selectRaw('method, COUNT(*) as payments_count, COALESCE(SUM(amount), 0) as total_received')
            ->groupBy('method')
            ->orderBy('method')
            ->get();

        return [
            'period' => [
                'type' => $period,
                'date_from' => $start->toDateString(),
                'date_to' => $end->toDateString(),
                'timezone' => config('app.timezone'),
            ],
            'summary' => [
                'total_attendances' => (int) $summary->total_attendances,
                'unique_patients' => (int) $summary->unique_patients,
                'total_patients' => (int) $summary->unique_patients,
                'total_charged' => Money::format($summary->total_charged),
                'received_for_attendances' => Money::format($summary->received_for_attendances),
                'total_pending' => Money::format($summary->total_pending),
                'cash_received_in_period' => Money::format($cashReceived),
            ],
            'payment_status' => [
                'paid' => (int) ($paymentStatus->paid ?? 0),
                'partial' => (int) ($paymentStatus->partial ?? 0),
                'unpaid' => (int) ($paymentStatus->unpaid ?? 0),
            ],
            'by_doctor' => $byDoctor,
            'by_speciality' => $bySpeciality,
            'by_procedure' => $byProcedure,
            'payments_by_method' => $paymentsByMethod,
        ];
    }

    public function attendanceQuery(array $filters): Builder
    {
        [$start, $end] = $this->resolvePeriod($filters);
        $query = Attendance::query()
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()]);

        if (isset($filters['doctor_id'])) {
            $query->where('doctor_id', $filters['doctor_id']);
        }

        if (isset($filters['patient_id'])) {
            $query->where('patient_id', $filters['patient_id']);
        }

        return $query;
    }

    /**
     * @return array{Carbon, Carbon, string}
     */
    public function resolvePeriod(array $filters): array
    {
        $period = $filters['period'] ?? 'daily';

        return match ($period) {
            'monthly' => $this->monthlyPeriod($filters['month'] ?? now()->format('Y-m')),
            'annual' => $this->annualPeriod((int) ($filters['year'] ?? now()->year)),
            'custom' => [Carbon::parse($filters['date_from']), Carbon::parse($filters['date_to']), 'custom'],
            default => $this->dailyPeriod($filters['date'] ?? now()->toDateString()),
        };
    }

    /** @return array{Carbon, Carbon, string} */
    private function dailyPeriod(string $date): array
    {
        $day = Carbon::parse($date);

        return [$day->copy(), $day->copy(), 'daily'];
    }

    /** @return array{Carbon, Carbon, string} */
    private function monthlyPeriod(string $month): array
    {
        $date = Carbon::createFromFormat('Y-m', $month)->startOfMonth();

        return [$date->copy(), $date->copy()->endOfMonth(), 'monthly'];
    }

    /** @return array{Carbon, Carbon, string} */
    private function annualPeriod(int $year): array
    {
        $date = Carbon::create($year, 1, 1);

        return [$date->copy(), $date->copy()->endOfYear(), 'annual'];
    }
}
