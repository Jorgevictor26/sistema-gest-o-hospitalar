<?php

namespace App\Services;

use App\DTOs\PatientDTO;
use App\Models\Patient;
use App\Repositories\PatientRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PatientService
{
    public function __construct(private readonly PatientRepository $patients) {}

    public function list(?string $search, ?bool $active, int $perPage): LengthAwarePaginator
    {
        return $this->patients->paginate($search, $active, $perPage);
    }

    public function create(PatientDTO $data): Patient
    {
        return $this->patients->create($data->values)->loadCount('attendances');
    }

    public function update(Patient $patient, PatientDTO $data): Patient
    {
        return $this->patients->update($patient, $data->values)->loadCount('attendances');
    }

    public function changeStatus(Patient $patient, bool $active): Patient
    {
        return $this->patients->update($patient, ['is_active' => $active])->loadCount('attendances');
    }

    public function history(Patient $patient, int $perPage): LengthAwarePaginator
    {
        return $this->patients->history($patient, $perPage);
    }
}
