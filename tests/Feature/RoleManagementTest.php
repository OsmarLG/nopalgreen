<?php

use App\Models\User;
use Database\Seeders\RolesAndMasterUserSeeder;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

test('admin can view roles and permissions pages', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create([
        'username' => 'admin01',
    ]);
    $admin->assignRole('admin');

    $rolesResponse = $this->actingAs($admin)->get(route('roles.index'));
    $rolesResponse->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('roles/index')
        ->has('roles.data')
    );

    $permissionsResponse = $this->actingAs($admin)->get(route('permissions.index'));
    $permissionsResponse->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('permissions/index')
        ->has('permissions.data')
    );
});

test('admin can not edit protected master role', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create([
        'username' => 'admin02',
    ]);
    $admin->assignRole('admin');

    $masterRole = Role::findByName('master', 'web');

    $response = $this->actingAs($admin)->get(route('roles.edit', $masterRole));

    $response->assertForbidden();
});

test('admin can create a new role', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create([
        'username' => 'admin03',
    ]);
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->post(route('roles.store'), [
        'name' => 'supervisor',
        'permissions' => ['users.view', 'permissions.view'],
    ]);

    $response->assertRedirect();

    $role = Role::findByName('supervisor', 'web');

    expect($role->hasPermissionTo('users.view'))->toBeTrue();
    expect($role->hasPermissionTo('permissions.view'))->toBeTrue();
});
