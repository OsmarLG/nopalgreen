<?php

use App\Models\RawMaterial;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RolesAndMasterUserSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('admin can view the suppliers page', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    Supplier::factory()->count(2)->create();

    $admin = User::factory()->create([
        'username' => 'supadmin01',
    ]);
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get(route('suppliers.index'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('suppliers/index')
        ->has('suppliers.data', 2)
    );
});

test('admin can create a supplier', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create([
        'username' => 'supadmin02',
    ]);
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->post(route('suppliers.store'), [
        'name' => 'Proveedor Central',
        'contact_name' => 'Laura Perez',
        'phone' => '6140000000',
        'email' => 'COMPRAS@PROVEEDOR.LOCAL',
        'address' => 'Av Principal 123',
        'is_active' => true,
    ]);

    $response->assertRedirect();

    $supplier = Supplier::query()->where('name', 'Proveedor Central')->firstOrFail();

    expect($supplier->email)->toBe('compras@proveedor.local');
    expect($supplier->is_active)->toBeTrue();
});

test('admin can update a supplier', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create([
        'username' => 'supadmin03',
    ]);
    $admin->assignRole('admin');

    $supplier = Supplier::factory()->create([
        'name' => 'Proveedor Norte',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->patch(route('suppliers.update', $supplier), [
        'name' => 'Proveedor Norte Actualizado',
        'contact_name' => 'Mario Gomez',
        'phone' => '6141111111',
        'email' => 'norte@proveedor.local',
        'address' => 'Calle Secundaria 45',
        'is_active' => false,
    ]);

    $response->assertRedirect();

    $supplier->refresh();

    expect($supplier->name)->toBe('Proveedor Norte Actualizado');
    expect($supplier->is_active)->toBeFalse();
});

test('admin can delete unused suppliers and deactivate suppliers already in use', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create([
        'username' => 'supadmin04',
    ]);
    $admin->assignRole('admin');

    $unusedSupplier = Supplier::factory()->create();
    $usedSupplier = Supplier::factory()->create(['is_active' => true]);
    $rawMaterial = RawMaterial::factory()->create();

    $rawMaterial->supplierLinks()->create([
        'supplier_id' => $usedSupplier->id,
        'supplier_sku' => 'RM-USED',
        'cost' => null,
        'is_primary' => true,
    ]);

    $deleteResponse = $this->actingAs($admin)->delete(route('suppliers.destroy', $unusedSupplier));
    $deleteResponse->assertRedirect(route('suppliers.index'));
    $this->assertDatabaseMissing('suppliers', ['id' => $unusedSupplier->id]);

    $toggleResponse = $this->actingAs($admin)->patch(route('suppliers.toggle-active', $usedSupplier));
    $toggleResponse->assertRedirect(route('suppliers.index'));

    $usedSupplier->refresh();

    expect($usedSupplier->is_active)->toBeFalse();
});
