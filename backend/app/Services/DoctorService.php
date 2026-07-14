<?php

namespace App\Repositories;

use App\DTOs\DoctorDTO;
use App\Models\Doctor;
use App\Models\Role;
use App\Models\User;
use App\Repositories\DoctorRepository;
use Illuminate\Support\Facades\Hash;

class DoctorService
{
    public function __construct(
        private readonly DoctorRepository $doctors,
        private readonly UserRepository $users,
    ) {}

    public function create(DoctorDTO $data): Doctor
    {
        $user = $this->users->create(
            [
                'name' => $data->name,
                'email' => $data->email,
                'phone_number' => $data->phone_number,
                'speciality' => $data->speciality,
                'password' => Hash::make($data->password),
            ]
        );

        $role = Role::where('role', 'doctor');

        $user->roles()->attach($role->id);

        $doctor = $this->doctors->create(
            [
                'user_id' => $user->id,
                'speciality' => $data->speciality
            ]
        );
        return $doctor;
    }
}
