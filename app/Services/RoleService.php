<?php

namespace App\Services;

use App\Support\Access;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleService
{
    /**
     * Paginate roles for index page.
     *
     * @return LengthAwarePaginator<int, Role>
     */
    public function paginateForIndex(?string $search = null): LengthAwarePaginator
    {
        return Role::query()
            ->with('permissions:id,name')
            ->withCount('users')
            ->when($search, function ($query, string $searchTerm) {
                $query->where(function ($nestedQuery) use ($searchTerm) {
                    $nestedQuery
                        ->where('name', 'like', "%{$searchTerm}%")
                        ->orWhereHas('permissions', fn ($permissionQuery) => $permissionQuery->where('name', 'like', "%{$searchTerm}%"));
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();
    }

    /**
     * Paginate permissions for index page.
     *
     * @return LengthAwarePaginator<int, Permission>
     */
    public function paginatePermissions(?string $search = null): LengthAwarePaginator
    {
        return Permission::query()
            ->with('roles:id,name')
            ->when($search, function ($query, string $searchTerm) {
                $query->where(function ($nestedQuery) use ($searchTerm) {
                    $nestedQuery
                        ->where('name', 'like', "%{$searchTerm}%")
                        ->orWhereHas('roles', fn ($roleQuery) => $roleQuery->where('name', 'like', "%{$searchTerm}%"));
                });
            })
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();
    }

    /**
     * Return available permissions.
     *
     * @return list<string>
     */
    public function availablePermissions(): array
    {
        return Permission::query()
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }

    /**
     * Create a role and sync permissions.
     *
     * @param  array{name:string,permissions?:array<int,string>}  $data
     */
    public function create(array $data): Role
    {
        return DB::transaction(function () use ($data): Role {
            $role = Role::query()->create([
                'name' => Str::lower($data['name']),
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($data['permissions'] ?? []);

            return $role->load('permissions:id,name');
        });
    }

    /**
     * Update role permissions.
     *
     * @param  array{permissions?:array<int,string>}  $data
     */
    public function update(Role $role, array $data): Role
    {
        return DB::transaction(function () use ($role, $data): Role {
            $role->syncPermissions($data['permissions'] ?? []);

            return $role->load('permissions:id,name');
        });
    }

    public function isProtected(Role $role): bool
    {
        return Access::isProtectedRoleName($role->name);
    }
}
