<?php

namespace App\Services;

use App\DTOs\PasswordResetDTO;
use App\DTOs\RegisterDTO;
use App\DTOs\RoleAssignmentDTO;
use App\DTOs\UpdateUserDTO;
use App\Models\Role;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class UserService
{
    public function __construct(private readonly UserRepository $users) {}

    public function list(array $filters): LengthAwarePaginator
    {
        return $this->users->paginate($filters);
    }

    public function create(RegisterDTO $data): User
    {
        return DB::transaction(function () use ($data): User {
            $user = $this->users->create([
                'name' => $data->name,
                'email' => $data->email,
                'phone_number' => $data->phone_number,
                'password' => Hash::make($data->password),
                'is_active' => true,
            ]);
            $roleIds = Role::whereIn('name', $data->roles)->pluck('id');
            $user->roles()->sync($roleIds);

            return $user->load(['roles', 'doctor']);
        });
    }

    public function update(User $user, UpdateUserDTO $data): User
    {
        return $this->users->update($user, $data->toArray());
    }

    public function syncRoles(User $user, RoleAssignmentDTO $data): User
    {
        $roles = Role::whereIn('name', $data->roles)->get();
        $this->ensureDoctorRoleIntegrity($user, $roles->pluck('name')->all());
        $user->roles()->sync($roles->modelKeys());

        return $user->load(['roles', 'doctor']);
    }

    public function addRole(User $user, Role $role): User
    {
        if ($role->name === 'doctor' && ! $user->doctor()->exists()) {
            throw new ConflictHttpException('A role doctor só pode ser atribuída através do cadastro de médico.');
        }

        $user->roles()->syncWithoutDetaching([$role->id]);

        return $user->load(['roles', 'doctor']);
    }

    public function removeRole(User $user, Role $role): User
    {
        if (! $user->roles()->whereKey($role->id)->exists()) {
            throw new ConflictHttpException('O utilizador não possui esta role.');
        }

        if ($user->roles()->count() <= 1) {
            throw new ConflictHttpException('Não é permitido remover a última role do utilizador.');
        }

        if ($role->name === 'doctor' && $user->doctor()->exists()) {
            throw new ConflictHttpException('Não é permitido remover a role doctor de um perfil médico.');
        }

        $user->roles()->detach($role->id);

        return $user->load(['roles', 'doctor']);
    }

    public function changeStatus(User $user, bool $active, User $admin): User
    {
        if (! $active && $user->is($admin)) {
            throw new ConflictHttpException('O administrador não pode desactivar a própria conta.');
        }

        $user = $this->users->update($user, ['is_active' => $active]);

        if (! $active) {
            $user->tokens()->delete();
        }

        return $user;
    }

    public function resetPassword(User $user, PasswordResetDTO $data): void
    {
        $user->update(['password' => Hash::make($data->password)]);
        $user->tokens()->delete();
    }

    /** @param array<int, string> $roles */
    private function ensureDoctorRoleIntegrity(User $user, array $roles): void
    {
        $hasProfile = $user->doctor()->exists();

        if ($hasProfile && ! in_array('doctor', $roles, true)) {
            throw new ConflictHttpException('Um perfil médico deve manter a role doctor.');
        }

        if (! $hasProfile && in_array('doctor', $roles, true)) {
            throw new ConflictHttpException('A role doctor só pode ser atribuída através do cadastro de médico.');
        }
    }
}
