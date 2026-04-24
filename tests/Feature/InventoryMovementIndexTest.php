<?php

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\RawMaterial;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RolesAndMasterUserSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('admin can view inventory movements page with stock summary', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $warehouse = Warehouse::factory()->create(['name' => 'Almacen Principal']);
    $rawMaterial = RawMaterial::factory()->create(['name' => 'Maiz Blanco']);
    $product = Product::factory()->create(['name' => 'Tortilla Blanca']);

    InventoryMovement::factory()->create([
        'warehouse_id' => $warehouse->id,
        'item_type' => InventoryMovement::ITEM_TYPE_RAW_MATERIAL,
        'item_id' => $rawMaterial->id,
        'movement_type' => InventoryMovement::TYPE_PURCHASE,
        'direction' => InventoryMovement::DIRECTION_IN,
        'quantity' => 100,
    ]);

    InventoryMovement::factory()->create([
        'warehouse_id' => $warehouse->id,
        'item_type' => InventoryMovement::ITEM_TYPE_PRODUCT,
        'item_id' => $product->id,
        'movement_type' => InventoryMovement::TYPE_PRODUCTION_OUTPUT,
        'direction' => InventoryMovement::DIRECTION_IN,
        'quantity' => 45,
    ]);

    $admin = User::factory()->create(['username' => 'inventoryadmin01']);
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get(route('inventory-movements.index'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('inventory-movements/index')
        ->has('movements.data', 2)
        ->has('stockSummary', 2)
    );
});
