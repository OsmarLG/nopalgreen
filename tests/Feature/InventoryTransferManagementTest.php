<?php

use App\Models\InventoryMovement;
use App\Models\InventoryTransfer;
use App\Models\Product;
use App\Models\RawMaterial;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RolesAndMasterUserSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('admin can view inventory transfers page', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $transfer = InventoryTransfer::factory()->create();

    $admin = User::factory()->create(['username' => 'transferadmin01']);
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get(route('inventory-transfers.index'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('inventory-transfers/index')
        ->has('transfers.data', 1)
        ->where('transfers.data.0.id', $transfer->id)
    );
});

test('admin can create inventory transfer and sync paired movements', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create(['username' => 'transferadmin02']);
    $admin->assignRole('admin');

    $sourceWarehouse = Warehouse::factory()->create(['name' => 'Planta']);
    $destinationWarehouse = Warehouse::factory()->create(['name' => 'Reparto']);
    $rawMaterial = RawMaterial::factory()->create();

    $response = $this->actingAs($admin)->post(route('inventory-transfers.store'), [
        'source_warehouse_id' => $sourceWarehouse->id,
        'destination_warehouse_id' => $destinationWarehouse->id,
        'item_type' => InventoryMovement::ITEM_TYPE_RAW_MATERIAL,
        'item_id' => $rawMaterial->id,
        'quantity' => 20,
        'unit_cost' => 45.50,
        'transferred_at' => now()->format('Y-m-d H:i:s'),
        'notes' => 'Transferencia a reparto',
    ]);

    $response->assertRedirect();

    $transfer = InventoryTransfer::query()->firstOrFail();

    $this->assertDatabaseHas('inventory_movements', [
        'warehouse_id' => $sourceWarehouse->id,
        'item_type' => InventoryMovement::ITEM_TYPE_RAW_MATERIAL,
        'item_id' => $rawMaterial->id,
        'movement_type' => InventoryMovement::TYPE_TRANSFER,
        'direction' => InventoryMovement::DIRECTION_OUT,
        'quantity' => '20.000',
        'unit_cost' => '45.50',
        'reference_type' => InventoryTransfer::class,
        'reference_id' => $transfer->id,
    ]);

    $this->assertDatabaseHas('inventory_movements', [
        'warehouse_id' => $destinationWarehouse->id,
        'item_type' => InventoryMovement::ITEM_TYPE_RAW_MATERIAL,
        'item_id' => $rawMaterial->id,
        'movement_type' => InventoryMovement::TYPE_TRANSFER,
        'direction' => InventoryMovement::DIRECTION_IN,
        'quantity' => '20.000',
        'unit_cost' => '45.50',
        'reference_type' => InventoryTransfer::class,
        'reference_id' => $transfer->id,
    ]);
});

test('admin can update transfer and movements are recalculated', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create(['username' => 'transferadmin03']);
    $admin->assignRole('admin');

    $firstSource = Warehouse::factory()->create();
    $firstDestination = Warehouse::factory()->create();
    $secondSource = Warehouse::factory()->create();
    $secondDestination = Warehouse::factory()->create();
    $rawMaterial = RawMaterial::factory()->create();
    $product = Product::factory()->create();

    $transfer = InventoryTransfer::factory()->create([
        'source_warehouse_id' => $firstSource->id,
        'destination_warehouse_id' => $firstDestination->id,
        'item_type' => InventoryMovement::ITEM_TYPE_RAW_MATERIAL,
        'item_id' => $rawMaterial->id,
        'quantity' => 8,
        'unit_cost' => 10,
    ]);

    InventoryMovement::factory()->create([
        'warehouse_id' => $firstSource->id,
        'item_type' => InventoryMovement::ITEM_TYPE_RAW_MATERIAL,
        'item_id' => $rawMaterial->id,
        'movement_type' => InventoryMovement::TYPE_TRANSFER,
        'direction' => InventoryMovement::DIRECTION_OUT,
        'quantity' => 8,
        'unit_cost' => 10,
        'reference_type' => InventoryTransfer::class,
        'reference_id' => $transfer->id,
    ]);

    InventoryMovement::factory()->create([
        'warehouse_id' => $firstDestination->id,
        'item_type' => InventoryMovement::ITEM_TYPE_RAW_MATERIAL,
        'item_id' => $rawMaterial->id,
        'movement_type' => InventoryMovement::TYPE_TRANSFER,
        'direction' => InventoryMovement::DIRECTION_IN,
        'quantity' => 8,
        'unit_cost' => 10,
        'reference_type' => InventoryTransfer::class,
        'reference_id' => $transfer->id,
    ]);

    $response = $this->actingAs($admin)->patch(route('inventory-transfers.update', $transfer), [
        'source_warehouse_id' => $secondSource->id,
        'destination_warehouse_id' => $secondDestination->id,
        'item_type' => InventoryMovement::ITEM_TYPE_PRODUCT,
        'item_id' => $product->id,
        'quantity' => 14,
        'unit_cost' => 32,
        'transferred_at' => now()->format('Y-m-d H:i:s'),
        'notes' => 'Reubicar producto terminado',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseMissing('inventory_movements', [
        'warehouse_id' => $firstSource->id,
        'reference_type' => InventoryTransfer::class,
        'reference_id' => $transfer->id,
    ]);

    $this->assertDatabaseHas('inventory_movements', [
        'warehouse_id' => $secondSource->id,
        'item_type' => InventoryMovement::ITEM_TYPE_PRODUCT,
        'item_id' => $product->id,
        'direction' => InventoryMovement::DIRECTION_OUT,
        'quantity' => '14.000',
        'reference_type' => InventoryTransfer::class,
        'reference_id' => $transfer->id,
    ]);

    $this->assertDatabaseHas('inventory_movements', [
        'warehouse_id' => $secondDestination->id,
        'item_type' => InventoryMovement::ITEM_TYPE_PRODUCT,
        'item_id' => $product->id,
        'direction' => InventoryMovement::DIRECTION_IN,
        'quantity' => '14.000',
        'reference_type' => InventoryTransfer::class,
        'reference_id' => $transfer->id,
    ]);
});

test('admin can delete transfer and its generated movements', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create(['username' => 'transferadmin04']);
    $admin->assignRole('admin');

    $transfer = InventoryTransfer::factory()->create();

    InventoryMovement::factory()->create([
        'warehouse_id' => $transfer->source_warehouse_id,
        'item_type' => $transfer->item_type,
        'item_id' => $transfer->item_id,
        'movement_type' => InventoryMovement::TYPE_TRANSFER,
        'direction' => InventoryMovement::DIRECTION_OUT,
        'quantity' => $transfer->quantity,
        'reference_type' => InventoryTransfer::class,
        'reference_id' => $transfer->id,
    ]);

    InventoryMovement::factory()->create([
        'warehouse_id' => $transfer->destination_warehouse_id,
        'item_type' => $transfer->item_type,
        'item_id' => $transfer->item_id,
        'movement_type' => InventoryMovement::TYPE_TRANSFER,
        'direction' => InventoryMovement::DIRECTION_IN,
        'quantity' => $transfer->quantity,
        'reference_type' => InventoryTransfer::class,
        'reference_id' => $transfer->id,
    ]);

    $response = $this->actingAs($admin)->delete(route('inventory-transfers.destroy', $transfer));

    $response->assertRedirect(route('inventory-transfers.index'));

    $this->assertDatabaseMissing('inventory_transfers', ['id' => $transfer->id]);
    $this->assertDatabaseMissing('inventory_movements', [
        'reference_type' => InventoryTransfer::class,
        'reference_id' => $transfer->id,
    ]);
});
