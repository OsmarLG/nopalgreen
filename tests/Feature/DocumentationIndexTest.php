<?php

use App\Models\User;
use Database\Seeders\RolesAndMasterUserSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('admin can view documentation bank and module guide', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create(['username' => 'docs-admin']);
    $admin->assignRole('admin');

    $indexResponse = $this->actingAs($admin)->get(route('documentation.index'));

    $indexResponse->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('documentation/index')
        ->has('groups.Configuracion')
        ->has('groups.Ventas')
    );

    $showResponse = $this->actingAs($admin)->get(route('documentation.show', 'usuarios'));

    $showResponse->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('documentation/show')
        ->where('entry.slug', 'usuarios')
        ->where('entry.title', 'Usuarios')
        ->where('entry.section', 'Configuracion')
    );
});

test('employee only sees documentation allowed for attendance and dashboard', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $employee = User::factory()->create(['username' => 'docs-employee']);
    $employee->assignRole('empleado');

    $response = $this->actingAs($employee)->get(route('documentation.index'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('documentation/index')
        ->where('groups.Plataforma.0.slug', 'dashboard')
        ->has('groups.Configuracion', 1)
        ->where('groups.Configuracion.0.slug', 'asistencia')
    );

    $blockedResponse = $this->actingAs($employee)->get(route('documentation.show', 'usuarios'));
    $blockedResponse->assertNotFound();

    $allowedResponse = $this->actingAs($employee)->get(route('documentation.show', 'asistencia'));
    $allowedResponse->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('documentation/show')
        ->where('entry.slug', 'asistencia')
    );
});
