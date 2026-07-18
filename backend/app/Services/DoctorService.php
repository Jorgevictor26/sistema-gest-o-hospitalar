<?php

namespace App\Services;

use App\DTOs\DoctorDTO;
use App\Models\Doctor;
use App\Models\Role;
use App\Repositories\DoctorRepository;
use App\Repositories\UserRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DoctorService
{
    public function __construct(
        private readonly DoctorRepository $doctors,
        private readonly UserRepository $users,
    ) {}

    public function list(?string $search, ?bool $active, int $perPage): LengthAwarePaginator
    {
        return $this->doctors->paginate($search, $active, $perPage);
    }

    public function create(DoctorDTO $data): Doctor
    {
        return DB::transaction(function () use ($data): Doctor {
            $user = $this->users->create([
                'name' => $data->name,
                'email' => $data->email,
                'phone_number' => $data->phone_number,
                'password' => Hash::make($data->password),
                'is_active' => true,
            ]);

            $roleNames = array_values(array_unique([...$data->roles, 'doctor']));
            $roleIds = Role::query()
                ->whereIn('name', $roleNames)
                ->pluck('id');

            $user->roles()->sync($roleIds);

            return $this->doctors->create([
                'user_id' => $user->id,
                'speciality' => $data->speciality,
            ])->load('user.roles');
        });
    }
}
