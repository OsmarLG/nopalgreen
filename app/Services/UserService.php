<?php

namespace App\Services;

use App\Models\User;
use App\Support\Access;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserService
{
    /**
     * Paginate users for the index page.
     *
     * @return LengthAwarePaginator<int, User>
     */
    public function paginateForIndex(?string $search = null): LengthAwarePaginator
    {
        return User::query()
            ->with(['roles:id,name', 'permissions:id,name'])
            ->when($search, function ($query, string $searchTerm) {
                $query->where(function ($nestedQuery) use ($searchTerm) {
                    $nestedQuery
                        ->where('name', 'like', "%{$searchTerm}%")
                        ->orWhere('username', 'like', "%{$searchTerm}%")
                        ->orWhere('email', 'like', "%{$searchTerm}%")
                        ->orWhereHas('roles', fn ($roleQuery) => $roleQuery->where('name', 'like', "%{$searchTerm}%"));
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();
    }

    /**
     * Return available roles.
     *
     * @return list<string>
     */
    public function availableRoles(User $actor): array
    {
        return Role::query()
            ->when(
                ! Access::canManageProtectedRecords($actor),
                fn ($query) => $query->whereNotIn('name', Access::PROTECTED_ROLES),
            )
            ->orderBy('name')
            ->pluck('name')
            ->all();
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
     * Create a user and sync access control.
     *
     * @param  array{name:string,username:string,email:string,password:string,roles:array<int,string>,attendance_starts_at?:string|null,permissions?:array<int,string>}  $data
     */
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $user = User::query()->create([
                'name' => $data['name'],
                'username' => Str::lower($data['username']),
                'email' => Str::lower($data['email']),
                'password' => $data['password'],
                'attendance_starts_at' => $data['attendance_starts_at'] ?? null,
            ]);

            $user->syncRoles($data['roles']);
            $user->syncPermissions($data['permissions'] ?? []);

            return $user->load(['roles:id,name', 'permissions:id,name']);
        });
    }

    /**
     * Update a user and sync access control.
     *
     * @param  array{name:string,username:string,email:string,password?:string|null,roles:array<int,string>,attendance_starts_at?:string|null,permissions?:array<int,string>}  $data
     */
    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $attributes = [
                'name' => $data['name'],
                'username' => Str::lower($data['username']),
                'email' => Str::lower($data['email']),
                'attendance_starts_at' => $data['attendance_starts_at'] ?? null,
            ];

            if (! empty($data['password'])) {
                $attributes['password'] = $data['password'];
            }

            $user->fill($attributes);
            $user->save();

            $user->syncRoles($data['roles']);
            $user->syncPermissions($data['permissions'] ?? []);

            return $user->load(['roles:id,name', 'permissions:id,name']);
        });
    }

    /**
     * Delete a user.
     */
    public function delete(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user->syncRoles([]);
            $user->syncPermissions([]);
            $user->delete();
        });
    }
}
