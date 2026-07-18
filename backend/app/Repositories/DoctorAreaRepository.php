<?php

namespace App\Repositories;

use App\Models\Attendance;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DoctorAreaRepository
{
    public function doctorFor(User $user): Doctor
    {
        $user->loadMissing('doctor.user');

        return $user->doctor
            ?? throw new NotFoundHttpException('O utilizador não possui um perfil médico associado.');
    }

    public function patients(Doctor $doctor, ?string $search, int $perPage): LengthAwarePaginator
    {
        return Patient::query()
            ->whereHas('attendances', fn (Builder $query) => $query->where('doctor_id', $doctor->id))
            ->when($search, function (Builder $query, string $search): void {
                $query->where(fn (Builder $query) => $query->where('name', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%")
                    ->orWhere('identity_card', 'like', "%{$search}%"));
            })
            ->withCount(['attendances as doctor_attendances_count' => fn (Builder $query) => $query->where('doctor_id', $doctor->id)])
            ->addSelect(['last_attendance_date' => Attendance::query()->select('attendance_date')
                ->whereColumn('patient_id', 'patients.id')->where('doctor_id', $doctor->id)
                ->latest('attendance_date')->limit(1)])
            ->orderBy('name')->paginate($perPage)->withQueryString();
    }

    public function patientHistory(Doctor $doctor, Patient $patient, int $perPage): LengthAwarePaginator
    {
        return Attendance::query()->where('doctor_id', $doctor->id)->where('patient_id', $patient->id)
            ->with(['patient', 'doctor.user', 'procedures', 'registeredBy'])
            ->latest('attendance_date')->paginate($perPage)->withQueryString();
    }

    public function attendancesBetween(Doctor $doctor, Carbon $start, Carbon $end): Builder
    {
        return Attendance::query()->where('doctor_id', $doctor->id)
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()]);
    }

    public function commissions(Doctor $doctor, Carbon $start, Carbon $end, int $perPage): LengthAwarePaginator
    {
        return $this->attendancesBetween($doctor, $start, $end)
            ->with(['patient', 'doctor.user', 'procedures', 'registeredBy'])
            ->latest('attendance_date')->latest('id')->paginate($perPage)->withQueryString();
    }

    public function topProcedures(Doctor $doctor, Carbon $start, Carbon $end): Collection
    {
        return $doctor->attendances()->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->join('attendance_procedure', 'attendances.id', '=', 'attendance_procedure.attendance_id')
            ->join('procedures', 'procedures.id', '=', 'attendance_procedure.procedure_id')
            ->groupBy('procedures.id', 'procedures.procedure')->orderByDesc('total_performed')->limit(10)
            ->get(['procedures.id', 'procedures.procedure', DB::raw('COUNT(*) as total_performed')]);
    }
}
