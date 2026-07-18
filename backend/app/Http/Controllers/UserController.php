<?php

namespace App\Http\Controllers;

use App\DTOs\PasswordResetDTO;
use App\DTOs\RegisterDTO;
use App\DTOs\RoleAssignmentDTO;
use App\DTOs\UpdateUserDTO;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\ChangeUserStatusRequest;
use App\Http\Requests\ManageUserRoleRequest;
use App\Http\Requests\ResetUserPasswordRequest;
use App\Http\Requests\SyncUserRolesRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\AdminUserResource;
use App\Models\Role;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class UserController extends Controller
{
    public function __construct(private readonly UserService $userService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'role' => ['sometimes', 'string', 'exists:roles,name'],
            'active' => ['sometimes', 'boolean'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ]);
        if (array_key_exists('active', $filters)) {
            $filters['active'] = $request->boolean('active');
        }

        return AdminUserResource::collection($this->userService->list($filters));
    }

    public function store(RegisterRequest $request): JsonResponse
    {
        return (new AdminUserResource($this->userService->create(RegisterDTO::fromArray($request->validated()))))
            ->response()->setStatusCode(201);
    }

    public function show(User $user): AdminUserResource
    {
        return new AdminUserResource($user->load(['roles', 'doctor']));
    }

    public function update(UpdateUserRequest $request, User $user): AdminUserResource
    {
        return new AdminUserResource($this->userService->update($user, UpdateUserDTO::fromArray($request->validated())));
    }

    public function syncRoles(SyncUserRolesRequest $request, User $user): AdminUserResource
    {
        return new AdminUserResource($this->userService->syncRoles($user, RoleAssignmentDTO::fromArray($request->validated())));
    }

    public function addRole(ManageUserRoleRequest $request, User $user, Role $role): AdminUserResource
    {
        return new AdminUserResource($this->userService->addRole($user, $role));
    }

    public function removeRole(ManageUserRoleRequest $request, User $user, Role $role): AdminUserResource
    {
        return new AdminUserResource($this->userService->removeRole($user, $role));
    }

    public function changeStatus(ChangeUserStatusRequest $request, User $user): AdminUserResource
    {
        return new AdminUserResource($this->userService->changeStatus($user, $request->boolean('is_active'), $request->user()));
    }

    public function resetPassword(ResetUserPasswordRequest $request, User $user): Response
    {
        $this->userService->resetPassword($user, PasswordResetDTO::fromArray($request->validated()));

        return response()->noContent();
    }
}
