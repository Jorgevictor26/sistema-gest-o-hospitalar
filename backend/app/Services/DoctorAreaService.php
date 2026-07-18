<?php

namespace App\Services;

use App\DTOs\CommissionFilterDTO;
use App\DTOs\DoctorProfileDTO;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use App\Repositories\DoctorAreaRepository;
use App\Support\Money;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DoctorAreaService
{
    public function __construct(private readonly DoctorAreaRepository $repository) {}

    public function profile(User $user): Doctor
    {
        return $this->repository->doctorFor($user);
    }

    public function updateProfile(User $user, DoctorProfileDTO $dto): Doctor
    {
        $doctor = $this->profile($user);
        $doctor->user->update($dto->values);

        return $doctor->refresh()->load('user');
    }

    public function changePassword(User $user, string $password): void
    {
        DB::transaction(function () use ($user, $password): void {
            $user->update(['password' => $password]);
            $user->tokens()->delete();
        });
    }

    public function patients(User $user, ?string $search, int $perPage): LengthAwarePaginator
    {
        return $this->repository->patients($this->profile($user), $search, $perPage);
    }

    public function patientHistory(User $user, Patient $patient, int $perPage): LengthAwarePaginator
    {
        $history = $this->repository->patientHistory($this->profile($user), $patient, $perPage);
        abort_if($history->total() === 0, 404, 'Este paciente não possui histórico com o médico autenticado.');

        return $history;
    }

    /** @return array<string, mixed> */
    public function commissions(User $user, CommissionFilterDTO $dto): array
    {
        $doctor = $this->profile($user);
        [$start, $end, $period] = $this->period($dto->values);
        $query = $this->repository->attendancesBetween($doctor, $start, $end);
        $summary = (clone $query)->selectRaw('COUNT(*) as total_attendances')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_generated')
            ->selectRaw('COALESCE(SUM(total_amount * commission_percentage / 100), 0) as total_commission')->first();

        return [
            'period' => ['type' => $period, 'date_from' => $start->toDateString(), 'date_to' => $end->toDateString()],
            'summary' => [
                'total_attendances' => (int) $summary->total_attendances,
                'total_generated' => Money::format($summary->total_generated),
                'total_commission' => Money::format($summary->total_commission),
            ],
            'attendances' => $this->repository->commissions($doctor, $start, $end, (int) ($dto->values['per_page'] ?? 15)),
        ];
    }

    /** @return array<string, mixed> */
    public function dashboard(User $user): array
    {
        $doctor = $this->profile($user);
        $today = Carbon::today();
        $periods = [
            'today' => [$today->copy(), $today->copy()],
            'week' => [$today->copy()->startOfWeek(), $today->copy()->endOfWeek()],
            'month' => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()],
        ];
        $metrics = [];

        foreach ($periods as $key => [$start, $end]) {
            $row = $this->repository->attendancesBetween($doctor, $start, $end)
                ->selectRaw('COUNT(*) as attendances_count')
                ->selectRaw('COUNT(DISTINCT patient_id) as patients_count')
                ->selectRaw('COALESCE(SUM(total_amount), 0) as generated_value')
                ->selectRaw('COALESCE(SUM(total_amount * commission_percentage / 100), 0) as commission_amount')->first();
            $metrics[$key] = [
                'attendances_count' => (int) $row->attendances_count,
                'patients_count' => (int) $row->patients_count,
                'generated_value' => Money::format($row->generated_value),
                'commission_amount' => Money::format($row->commission_amount),
            ];
        }

        $recent = $doctor->attendances()->with(['patient', 'doctor.user', 'procedures', 'registeredBy'])
            ->latest('attendance_date')->latest('id')->limit(10)->get();

        $dailyFlow = $this->repository->attendancesBetween(
            $doctor, $today->copy()->subDays(29), $today
        )->selectRaw('attendance_date, COUNT(*) as total')
            ->groupBy('attendance_date')->orderBy('attendance_date')->get();
        $topProcedures = $this->repository->topProcedures(
            $doctor, $today->copy()->startOfMonth(), $today->copy()->endOfMonth()
        );

        return compact('doctor', 'metrics', 'recent', 'dailyFlow', 'topProcedures');
    }

    /** @return array{Carbon, Carbon, string} */
    private function period(array $filters): array
    {
        $period = $filters['period'] ?? 'monthly';
        $date = Carbon::parse($filters['date'] ?? today()->toDateString());

        return match ($period) {
            'daily' => [$date->copy(), $date->copy(), $period],
            'weekly' => [$date->copy()->startOfWeek(), $date->copy()->endOfWeek(), $period],
            'custom' => [Carbon::parse($filters['date_from']), Carbon::parse($filters['date_to']), $period],
            default => [$date->copy()->startOfMonth(), $date->copy()->endOfMonth(), 'monthly'],
        };
    }
}
