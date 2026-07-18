<?php

namespace App\Repositories;

use App\Models\Doctor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DoctorRepository
{
    public function paginate(?string $search, ?string $speciality, ?bool $active, int $perPage): LengthAwarePaginator
    {
        return Doctor::query()
            ->select('doctors.*')
            ->join('users', 'users.id', '=', 'doctors.user_id')
            ->with('user.roles')
            ->when($search, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('doctors.speciality', 'like', "%{$search}%")
                        ->orWhere('users.name', 'like', "%{$search}%")
                        ->orWhere('users.email', 'like', "%{$search}%");
                });
            })
            ->when($active !== null, function ($query) use ($active): void {
                $query->where('users.is_active', $active);
            })
            ->when($speciality, fn ($query, string $speciality) => $query->where('doctors.speciality', $speciality))
            ->orderBy('users.name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): Doctor
    {
        return Doctor::create($data);
    }

    public function update(Doctor $doctor, array $data): Doctor
    {
        $doctor->update($data);

        return $doctor->refresh();
    }

    public function statistics(Doctor $doctor): object
    {
        return $doctor->attendances()
            ->selectRaw('COUNT(*) as total_attendances')
            ->selectRaw('COUNT(DISTINCT patient_id) as unique_patients')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_charged')
            ->selectRaw('COALESCE(SUM(amount_paid), 0) as total_received')
            ->selectRaw('COALESCE(SUM(total_amount - amount_paid), 0) as total_pending')
            ->selectRaw('COALESCE(SUM(total_amount * commission_percentage / 100), 0) as total_commission')
            ->first();
    }
}
