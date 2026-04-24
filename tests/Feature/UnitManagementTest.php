<?php

use App\Models\RawMaterial;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolesAndMasterUserSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('admin can view the units page', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    Unit::factory()->count(2)->create();

    $admin = User::factory()->create([
        'username' => 'unitsadmin01',
    ]);
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get(route('units.index'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('units/index')
        ->has('units.data', 2)
    );
});

test('admin can create a unit', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create([
        'username' => 'unitsadmin02',
    ]);
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->post(route('units.store'), [
        'name' => 'Kilogramo Venta',
        'code' => 'KGV',
        'decimal_places' => 2,
    ]);

    $response->assertRedirect();

    $unit = Unit::query()->where('name', 'Kilogramo Venta')->firstOrFail();

    expect($unit->code)->toBe('kgv');
    expect($unit->decimal_places)->toBe(2);
});

test('admin can update a unit', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create([
        'username' => 'unitsadmin03',
    ]);
    $admin->assignRole('admin');

    $unit = Unit::factory()->create([
        'name' => 'Litro Base',
        'code' => 'ltb',
        'decimal_places' => 0,
    ]);

    $response = $this->actingAs($admin)->patch(route('units.update', $unit), [
        'name' => 'Litro Produccion',
        'code' => 'LTP',
        'decimal_places' => 3,
    ]);

    $response->assertRedirect();

    $unit->refresh();

    expect($unit->name)->toBe('Litro Produccion');
    expect($unit->code)->toBe('ltp');
    expect($unit->decimal_places)->toBe(3);
});

test('admin can delete unused units and deactivate units already in use', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create([
        'username' => 'unitsadmin04',
    ]);
    $admin->assignRole('admin');

    $unusedUnit = Unit::factory()->create();
    $usedUnit = Unit::factory()->create(['is_active' => true]);

    RawMaterial::factory()->create([
        'base_unit_id' => $usedUnit->id,
    ]);

    $deleteResponse = $this->actingAs($admin)->delete(route('units.destroy', $unusedUnit));
    $deleteResponse->assertRedirect(route('units.index'));
    $this->assertDatabaseMissing('units', ['id' => $unusedUnit->id]);

    $toggleResponse = $this->actingAs($admin)->patch(route('units.toggle-active', $usedUnit));
    $toggleResponse->assertRedirect(route('units.index'));

    $usedUnit->refresh();

    expect($usedUnit->is_active)->toBeFalse();
});
