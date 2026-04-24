<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\UserService;
use App\Support\Access;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(private UserService $userService) {}

    /**
     * Display a listing of users.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('users/index', [
            'filters' => [
                'search' => $request->string('search')->toString(),
            ],
            'users' => $this->userService->paginateForIndex($request->string('search')->toString()),
        ]);
    }

    /**
     * Show the form for creating a user.
     */
    public function create(): Response
    {
        return Inertia::render('users/create', [
            'roles' => $this->userService->availableRoles(request()->user()),
            'permissions' => $this->userService->availablePermissions(),
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = $this->userService->create($request->validated());

        return to_route('users.edit', $user)
            ->with('status', 'Usuario creado correctamente.');
    }

    /**
     * Show the form for editing a user.
     */
    public function edit(User $user): Response
    {
        $user->load(['roles:id,name', 'permissions:id,name']);
        abort_if(
            Access::userHasProtectedRole($user) && ! Access::canManageProtectedRecords(request()->user()),
            403,
            'No puedes editar usuarios protegidos.',
        );

        return Inertia::render('users/edit', [
            'roles' => $this->userService->availableRoles(request()->user()),
            'permissions' => $this->userService->availablePermissions(),
            'userRecord' => $user,
            'selectedRoles' => $user->roles->pluck('name')->values(),
            'selectedPermissions' => $user->permissions->pluck('name')->values(),
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->userService->update($user, $request->validated());

        return to_route('users.edit', $user)
            ->with('status', 'Usuario actualizado correctamente.');
    }

    /**
     * Remove the specified user.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if($request->user()?->is($user), 422, 'No puedes eliminar tu propio usuario.');
        abort_unless($request->user()?->can('users.delete'), 403);
        $user->loadMissing('roles:id,name');
        abort_if(
            Access::userHasProtectedRole($user) && ! Access::canManageProtectedRecords($request->user()),
            403,
            'No puedes eliminar usuarios protegidos.',
        );

        $this->userService->delete($user);

        return to_route('users.index')
            ->with('status', 'Usuario eliminado correctamente.');
    }
}
