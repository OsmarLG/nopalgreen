<?php

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\RawMaterial;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RolesAndMasterUserSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('admin can view inventory adjustments page', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $warehouse = Warehouse::factory()->create();
    $rawMaterial = RawMaterial::factory()->create();

    InventoryMovement::factory()->create([
        'warehouse_id' => $warehouse->id,
        'item_type' => InventoryMovement::ITEM_TYPE_RAW_MATERIAL,
        'item_id' => $rawMaterial->id,
        'movement_type' => InventoryMovement::TYPE_ADJUSTMENT,
        'direction' => InventoryMovement::DIRECTION_IN,
    ]);

    $admin = User::factory()->create(['username' => 'adjustmentadmin01']);
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get(route('inventory-adjustments.index'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('inventory-adjustments/index')
        ->has('adjustments.data', 1)
    );
});

test('admin can create an inventory adjustment', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create(['username' => 'adjustmentadmin02']);
    $admin->assignRole('admin');

    $warehouse = Warehouse::factory()->create();
    $rawMaterial = RawMaterial::factory()->create();

    $response = $this->actingAs($admin)->post(route('inventory-adjustments.store'), [
        'warehouse_id' => $warehouse->id,
        'item_type' => InventoryMovement::ITEM_TYPE_RAW_MATERIAL,
        'item_id' => $rawMaterial->id,
        'movement_type' => InventoryMovement::TYPE_ADJUSTMENT,
        'direction' => InventoryMovement::DIRECTION_IN,
        'quantity' => 12.5,
        'unit_cost' => 85.25,
        'moved_at' => now()->format('Y-m-d H:i:s'),
        'notes' => 'Conteo fisico mayor al sistema',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('inventory_movements', [
        'warehouse_id' => $warehouse->id,
        'item_type' => InventoryMovement::ITEM_TYPE_RAW_MATERIAL,
        'item_id' => $rawMaterial->id,
        'movement_type' => InventoryMovement::TYPE_ADJUSTMENT,
        'direction' => InventoryMovement::DIRECTION_IN,
        'quantity' => '12.500',
        'unit_cost' => '85.25',
        'notes' => 'Conteo fisico mayor al sistema',
    ]);
});

test('waste movements are always stored as outputs', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create(['username' => 'adjustmentadmin03']);
    $admin->assignRole('admin');

    $warehouse = Warehouse::factory()->create();
    $product = Product::factory()->create();

    $response = $this->actingAs($admin)->post(route('inventory-adjustments.store'), [
        'warehouse_id' => $warehouse->id,
        'item_type' => InventoryMovement::ITEM_TYPE_PRODUCT,
        'item_id' => $product->id,
        'movement_type' => InventoryMovement::TYPE_WASTE,
        'direction' => InventoryMovement::DIRECTION_IN,
        'quantity' => 3,
        'unit_cost' => null,
        'moved_at' => now()->format('Y-m-d H:i:s'),
        'notes' => 'Producto quebrado',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('inventory_movements', [
        'warehouse_id' => $warehouse->id,
        'item_type' => InventoryMovement::ITEM_TYPE_PRODUCT,
        'item_id' => $product->id,
        'movement_type' => InventoryMovement::TYPE_WASTE,
        'direction' => InventoryMovement::DIRECTION_OUT,
        'quantity' => '3.000',
    ]);
});

test('admin can update and delete manual inventory adjustments', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create(['username' => 'adjustmentadmin04']);
    $admin->assignRole('admin');

    $warehouse = Warehouse::factory()->create();
    $rawMaterial = RawMaterial::factory()->create();
    $product = Product::factory()->create();

    $adjustment = InventoryMovement::factory()->create([
        'warehouse_id' => $warehouse->id,
        'item_type' => InventoryMovement::ITEM_TYPE_RAW_MATERIAL,
        'item_id' => $rawMaterial->id,
        'movement_type' => InventoryMovement::TYPE_ADJUSTMENT,
        'direction' => InventoryMovement::DIRECTION_IN,
        'quantity' => 5,
    ]);

    $updateResponse = $this->actingAs($admin)->patch(route('inventory-adjustments.update', $adjustment), [
        'warehouse_id' => $warehouse->id,
        'item_type' => InventoryMovement::ITEM_TYPE_PRODUCT,
        'item_id' => $product->id,
        'movement_type' => InventoryMovement::TYPE_WASTE,
        'direction' => InventoryMovement::DIRECTION_IN,
        'quantity' => 2,
        'unit_cost' => 42,
        'moved_at' => now()->format('Y-m-d H:i:s'),
        'notes' => 'Merma por bolsa rota',
    ]);

    $updateResponse->assertRedirect();

    $this->assertDatabaseHas('inventory_movements', [
        'id' => $adjustment->id,
        'item_type' => InventoryMovement::ITEM_TYPE_PRODUCT,
        'item_id' => $product->id,
        'movement_type' => InventoryMovement::TYPE_WASTE,
        'direction' => InventoryMovement::DIRECTION_OUT,
        'quantity' => '2.000',
        'unit_cost' => '42.00',
    ]);

    $deleteResponse = $this->actingAs($admin)->delete(route('inventory-adjustments.destroy', $adjustment));
    $deleteResponse->assertRedirect(route('inventory-adjustments.index'));

    $this->assertDatabaseMissing('inventory_movements', [
        'id' => $adjustment->id,
    ]);
});
