<?php

namespace App\Repositories;

use App\Models\Patient;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PatientRepository
{
    public function paginate(?string $search, ?bool $active, int $perPage): LengthAwarePaginator
    {
        return Patient::query()
            ->withCount('attendances')
            ->when($active !== null, fn ($query) => $query->where('is_active', $active))
            ->when($search, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('phone_number', 'like', "%{$search}%")
                        ->orWhere('identity_card', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): Patient
    {
        return Patient::create($data);
    }

    public function update(Patient $patient, array $data): Patient
    {
        $patient->update($data);

        return $patient->refresh();
    }

    public function history(Patient $patient, int $perPage): LengthAwarePaginator
    {
        return $patient->attendances()
            ->with(['patient', 'doctor.user', 'procedures', 'registeredBy', 'payments.receiver'])
            ->latest('attendance_date')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }
}
