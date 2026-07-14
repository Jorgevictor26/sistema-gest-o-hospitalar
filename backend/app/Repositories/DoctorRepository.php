<?php

namespace App\Repositories;

use App\Models\Doctor;

class DoctorRepository
{
    public function create(array $data): Doctor
    {
        return  Doctor::create($data);
    }
}
