<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Services\RoleService;
use App\Support\Access;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct(private RoleService $roleService) {}

    public function index(Request $request): Response
    {
        return Inertia::render('roles/index', [
            'filters' => [
                'search' => $request->string('search')->toString(),
            ],
            'roles' => $this->roleService->paginateForIndex($request->string('search')->toString()),
            'protectedRoles' => Access::PROTECTED_ROLES,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('roles/create', [
            'permissions' => $this->roleService->availablePermissions(),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $role = $this->roleService->create($request->validated());

        return to_route('roles.edit', $role)
            ->with('status', 'Rol creado correctamente.');
    }

    public function edit(Role $role): Response
    {
        $role->load('permissions:id,name');
        abort_if(
            $this->roleService->isProtected($role) && ! Access::canManageProtectedRecords(request()->user()),
            403,
            'No puedes editar roles protegidos.',
        );

        return Inertia::render('roles/edit', [
            'roleRecord' => $role,
            'permissions' => $this->roleService->availablePermissions(),
            'selectedPermissions' => $role->permissions->pluck('name')->values(),
            'isProtected' => $this->roleService->isProtected($role),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $this->roleService->update($role, $request->validated());

        return to_route('roles.edit', $role)
            ->with('status', 'Rol actualizado correctamente.');
    }
}
