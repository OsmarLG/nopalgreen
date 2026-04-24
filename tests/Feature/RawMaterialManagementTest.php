<?php

use App\Models\RawMaterial;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolesAndMasterUserSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('admin can view the raw materials page', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    RawMaterial::factory()->count(2)->create();

    $admin = User::factory()->create([
        'username' => 'rawadmin01',
    ]);
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get(route('raw-materials.index'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('raw-materials/index')
        ->has('rawMaterials.data', 2)
    );
});

test('admin can create a raw material with a primary supplier', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create([
        'username' => 'rawadmin02',
    ]);
    $admin->assignRole('admin');

    $unit = Unit::factory()->create();
    $supplier = Supplier::factory()->create();

    $response = $this->actingAs($admin)->post(route('raw-materials.store'), [
        'name' => 'Maiz Blanco Selecto',
        'description' => 'Insumo principal para tortilla blanca.',
        'base_unit_id' => $unit->id,
        'supplier_id' => $supplier->id,
        'is_active' => true,
    ]);

    $response->assertRedirect();

    $rawMaterial = RawMaterial::query()->where('name', 'Maiz Blanco Selecto')->firstOrFail();
    $rawMaterial->load('supplierLinks');

    expect($rawMaterial->slug)->toBe('maiz-blanco-selecto');
    expect($rawMaterial->supplierLinks)->toHaveCount(1);
    expect($rawMaterial->supplierLinks->first()?->supplier_id)->toBe($supplier->id);
});

test('admin can update a raw material and remove its supplier', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create([
        'username' => 'rawadmin03',
    ]);
    $admin->assignRole('admin');

    $unit = Unit::factory()->create();
    $supplier = Supplier::factory()->create();
    $rawMaterial = RawMaterial::factory()->create([
        'name' => 'Harina Temporal',
        'slug' => 'harina-temporal',
        'base_unit_id' => $unit->id,
    ]);
    $rawMaterial->supplierLinks()->create([
        'supplier_id' => $supplier->id,
        'supplier_sku' => 'RM-BASE',
        'cost' => null,
        'is_primary' => true,
    ]);

    $response = $this->actingAs($admin)->patch(route('raw-materials.update', $rawMaterial), [
        'name' => 'Harina Refinada',
        'description' => 'Materia prima actualizada.',
        'base_unit_id' => $unit->id,
        'supplier_id' => null,
        'is_active' => false,
    ]);

    $response->assertRedirect();

    $rawMaterial->refresh();

    expect($rawMaterial->name)->toBe('Harina Refinada');
    expect($rawMaterial->slug)->toBe('harina-refinada');
    expect($rawMaterial->is_active)->toBeFalse();
    expect($rawMaterial->supplierLinks()->count())->toBe(0);
});

test('admin can delete unused raw materials and deactivate raw materials already in use', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create([
        'username' => 'rawadmin04',
    ]);
    $admin->assignRole('admin');

    $unusedRawMaterial = RawMaterial::factory()->create();
    $usedRawMaterial = RawMaterial::factory()->create(['is_active' => true]);
    $presentationUnit = Unit::factory()->create();

    $usedRawMaterial->presentations()->create([
        'name' => 'Costal 25 kg',
        'quantity' => 25,
        'unit_id' => $presentationUnit->id,
        'barcode' => null,
        'is_active' => true,
    ]);

    $deleteResponse = $this->actingAs($admin)->delete(route('raw-materials.destroy', $unusedRawMaterial));
    $deleteResponse->assertRedirect(route('raw-materials.index'));
    $this->assertDatabaseMissing('raw_materials', ['id' => $unusedRawMaterial->id]);

    $toggleResponse = $this->actingAs($admin)->patch(route('raw-materials.toggle-active', $usedRawMaterial));
    $toggleResponse->assertRedirect(route('raw-materials.index'));

    $usedRawMaterial->refresh();

    expect($usedRawMaterial->is_active)->toBeFalse();
});
