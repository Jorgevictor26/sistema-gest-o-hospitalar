<?php

namespace App\Repositories;

use App\Models\Doctor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DoctorRepository
{
    public function paginate(?string $search, ?bool $active, int $perPage): LengthAwarePaginator
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
            ->orderBy('users.name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): Doctor
    {
        return Doctor::create($data);
    }
}
