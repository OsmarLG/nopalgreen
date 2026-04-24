<?php

namespace App\Http\Controllers;

use App\Services\RoleService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PermissionController extends Controller
{
    public function __construct(private RoleService $roleService) {}

    public function index(Request $request): Response
    {
        return Inertia::render('permissions/index', [
            'filters' => [
                'search' => $request->string('search')->toString(),
            ],
            'permissions' => $this->roleService->paginatePermissions($request->string('search')->toString()),
        ]);
    }
}
