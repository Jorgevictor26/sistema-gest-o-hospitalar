<?php

namespace App\Services;

use App\DTOs\DoctorDTO;
use App\DTOs\UpdateDoctorDTO;
use App\Models\Doctor;
use App\Models\Role;
use App\Models\User;
use App\Repositories\DoctorRepository;
use App\Repositories\UserRepository;
use App\Support\Money;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class DoctorService
{
    public function __construct(
        private readonly DoctorRepository $doctors,
        private readonly UserRepository $users,
    ) {}

    public function list(?string $search, ?string $speciality, ?bool $active, int $perPage): LengthAwarePaginator
    {
        return $this->doctors->paginate($search, $speciality, $active, $perPage);
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

            $doctorRole = Role::where('name', 'doctor')->firstOrFail();
            $user->roles()->attach($doctorRole->id);

            return $this->doctors->create([
                'user_id' => $user->id,
                'speciality' => $data->speciality,
                'professional_number' => $data->professional_number,
                'commission_percentage' => $data->commission_percentage,
                'is_available' => $data->is_available,
            ])->load('user.roles');
        });
    }

    public function update(Doctor $doctor, UpdateDoctorDTO $data): Doctor
    {
        return DB::transaction(function () use ($doctor, $data): Doctor {
            $userData = array_intersect_key($data->values, array_flip(['name', 'email', 'phone_number']));
            $doctorData = array_intersect_key($data->values, array_flip(['speciality', 'professional_number', 'commission_percentage', 'is_available']));

            if ($userData !== []) {
                $this->users->update($doctor->user, $userData);
            }
            if ($doctorData !== []) {
                $this->doctors->update($doctor, $doctorData);
            }

            return $doctor->refresh()->load('user.roles');
        });
    }

    public function changeStatus(Doctor $doctor, bool $active, User $admin): Doctor
    {
        if (! $active && $doctor->user->is($admin)) {
            throw new ConflictHttpException('O administrador não pode desactivar a própria conta.');
        }

        $this->users->update($doctor->user, ['is_active' => $active]);

        if (! $active) {
            $doctor->user->tokens()->delete();
        }

        return $doctor->refresh()->load('user.roles');
    }

    /** @return array<string, int|string> */
    public function statistics(Doctor $doctor): array
    {
        $statistics = $this->doctors->statistics($doctor);

        return [
            'doctor_id' => $doctor->id,
            'total_attendances' => (int) $statistics->total_attendances,
            'unique_patients' => (int) $statistics->unique_patients,
            'total_charged' => Money::format($statistics->total_charged),
            'total_received' => Money::format($statistics->total_received),
            'total_pending' => Money::format($statistics->total_pending),
            'total_commission' => Money::format($statistics->total_commission),
        ];
    }

    public function updateCommission(Doctor $doctor, string $percentage): Doctor
    {
        $doctor->update(['commission_percentage' => $percentage]);

        return $doctor->refresh()->load('user.roles');
    }
}
