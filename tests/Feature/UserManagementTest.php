<?php

use App\Models\User;
use Database\Seeders\RolesAndMasterUserSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('master can view the user management page', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $master = User::where('username', 'osmarlg')->firstOrFail();

    $response = $this->actingAs($master)->get(route('users.index'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('users/index')
        ->has('users.data', 4)
        ->where('auth.user.username', 'osmarlg')
    );
});

test('planta users can not access user management', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $plantaUser = User::factory()->create([
        'username' => 'planta01',
    ]);
    $plantaUser->assignRole('planta');

    $response = $this->actingAs($plantaUser)->get(route('users.index'));

    $response->assertForbidden();
});

test('master can create a user with role and permissions', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $master = User::where('username', 'osmarlg')->firstOrFail();

    $response = $this->actingAs($master)->post(route('users.store'), [
        'name' => 'Usuario Planta',
        'username' => 'planta02',
        'email' => 'planta02@nopalgreen.local',
        'password' => 'password',
        'password_confirmation' => 'password',
        'roles' => ['planta', 'empleado'],
        'attendance_starts_at' => '2026-04-01',
        'permissions' => ['users.view'],
    ]);

    $response->assertRedirect();

    $createdUser = User::where('username', 'planta02')->firstOrFail();

    expect($createdUser->hasRole('planta'))->toBeTrue();
    expect($createdUser->hasRole('empleado'))->toBeTrue();
    expect($createdUser->can('users.view'))->toBeTrue();
    expect($createdUser->attendance_starts_at?->toDateString())->toBe('2026-04-01');
});

test('admin can not edit master user', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $master = User::where('username', 'osmarlg')->firstOrFail();

    $admin = User::factory()->create([
        'username' => 'admin04',
    ]);
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get(route('users.edit', $master));

    $response->assertForbidden();
});

test('admin can update non protected users', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create([
        'username' => 'admin05',
    ]);
    $admin->assignRole('admin');

    $plantaUser = User::factory()->create([
        'username' => 'planta03',
    ]);
    $plantaUser->assignRole('planta');

    $response = $this->actingAs($admin)->patch(route('users.update', $plantaUser), [
        'name' => 'Planta Editado',
        'username' => 'planta03',
        'email' => $plantaUser->email,
        'roles' => ['repartidor', 'empleado'],
        'attendance_starts_at' => '2026-04-10',
        'permissions' => [],
    ]);

    $response->assertRedirect();

    $plantaUser->refresh();

    expect($plantaUser->name)->toBe('Planta Editado');
    expect($plantaUser->hasRole('repartidor'))->toBeTrue();
    expect($plantaUser->hasRole('empleado'))->toBeTrue();
    expect($plantaUser->attendance_starts_at?->toDateString())->toBe('2026-04-10');
});
