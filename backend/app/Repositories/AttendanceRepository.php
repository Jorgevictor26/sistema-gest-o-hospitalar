<?php

namespace App\Repositories;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AttendanceRepository
{
    public function paginate(array $filters, User $user): LengthAwarePaginator
    {
        return Attendance::query()
            ->with(['patient', 'doctor.user', 'registeredBy', 'procedures'])
            ->when($user->hasRole('doctor') && ! $user->hasAnyRole(['admin', 'receptionist']), function ($query) use ($user): void {
                $query->whereHas('doctor', fn ($query) => $query->where('user_id', $user->id));
            })
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('attendance_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('attendance_date', '<=', $date))
            ->when($filters['doctor_id'] ?? null, fn ($query, $doctorId) => $query->where('doctor_id', $doctorId))
            ->when($filters['speciality'] ?? null, fn ($query, $speciality) => $query->whereHas('doctor', fn ($query) => $query->where('speciality', $speciality)))
            ->when($filters['patient_id'] ?? null, fn ($query, $patientId) => $query->where('patient_id', $patientId))
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->whereHas('patient', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('doctor.user', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($filters['payment_status'] ?? null, function ($query, string $status): void {
                match ($status) {
                    'unpaid' => $query->where('amount_paid', 0),
                    'partial' => $query->where('amount_paid', '>', 0)->whereColumn('amount_paid', '<', 'total_amount'),
                    'paid' => $query->whereColumn('amount_paid', '>=', 'total_amount'),
                };
            })
            ->latest('attendance_date')
            ->latest('id')
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();
    }

    public function create(array $data): Attendance
    {
        return Attendance::create($data);
    }

    public function update(Attendance $attendance, array $data): Attendance
    {
        $attendance->update($data);

        return $attendance;
    }

    public function delete(Attendance $attendance): void
    {
        $attendance->delete();
    }
}
