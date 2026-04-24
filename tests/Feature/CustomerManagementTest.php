<?php

use App\Models\Customer;
use App\Models\User;
use Database\Seeders\RolesAndMasterUserSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('admin can view the customers page', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    Customer::factory()->count(2)->create();

    $admin = User::factory()->create([
        'username' => 'custadmin01',
    ]);
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get(route('customers.index'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('customers/index')
        ->has('customers.data', 2)
    );
});

test('admin can create a customer', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create([
        'username' => 'custadmin02',
    ]);
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->post(route('customers.store'), [
        'name' => 'Ruta Centro',
        'customer_type' => 'Reparto',
        'phone' => '614-555-0101',
        'email' => 'Ruta.Centro@NopalGreen.local',
        'address' => 'Zona Centro',
        'is_active' => true,
    ]);

    $response->assertRedirect();

    $customer = Customer::query()->where('name', 'Ruta Centro')->firstOrFail();

    expect($customer->customer_type)->toBe('Reparto');
    expect($customer->email)->toBe('ruta.centro@nopalgreen.local');
});

test('admin can update a customer', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create([
        'username' => 'custadmin03',
    ]);
    $admin->assignRole('admin');

    $customer = Customer::factory()->create([
        'name' => 'Cliente Inicial',
        'customer_type' => 'Mostrador',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->patch(route('customers.update', $customer), [
        'name' => 'Cliente Actualizado',
        'customer_type' => 'Entrega',
        'phone' => '614-555-0202',
        'email' => 'cliente.actualizado@nopalgreen.local',
        'address' => 'Colonia Moderna',
        'is_active' => false,
    ]);

    $response->assertRedirect();

    $customer->refresh();

    expect($customer->name)->toBe('Cliente Actualizado');
    expect($customer->customer_type)->toBe('Entrega');
    expect($customer->is_active)->toBeFalse();
});

test('admin can delete unused customers', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create([
        'username' => 'custadmin04',
    ]);
    $admin->assignRole('admin');

    $customer = Customer::factory()->create();

    $response = $this->actingAs($admin)->delete(route('customers.destroy', $customer));

    $response->assertRedirect(route('customers.index'));
    $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
});
