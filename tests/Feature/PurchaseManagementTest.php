<?php

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductPresentation;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\RawMaterial;
use App\Models\RawMaterialPresentation;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RolesAndMasterUserSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('admin can view the purchases page', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    Purchase::factory()->create();

    $admin = User::factory()->create(['username' => 'purchaseadmin01']);
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get(route('purchases.index'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('purchases/index')
        ->has('purchases.data', 1)
    );
});

test('admin can create a purchase with mixed items', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create(['username' => 'purchaseadmin02']);
    $admin->assignRole('admin');

    $supplier = Supplier::factory()->create();
    $rawMaterial = RawMaterial::factory()->create();
    $rawMaterialPresentation = RawMaterialPresentation::factory()->create([
        'raw_material_id' => $rawMaterial->id,
    ]);
    $product = Product::factory()->create();
    $productPresentation = ProductPresentation::factory()->create([
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    $response = $this->actingAs($admin)->post(route('purchases.store'), [
        'supplier_id' => $supplier->id,
        'status' => Purchase::STATUS_DRAFT,
        'purchased_at' => now()->format('Y-m-d H:i:s'),
        'notes' => 'Compra inicial',
        'items' => [
            [
                'item_type' => PurchaseItem::ITEM_TYPE_RAW_MATERIAL,
                'item_id' => $rawMaterial->id,
                'presentation_type' => PurchaseItem::PRESENTATION_TYPE_RAW_MATERIAL,
                'presentation_id' => $rawMaterialPresentation->id,
                'quantity' => 4,
                'unit_cost' => 250,
                'total' => 1000,
            ],
            [
                'item_type' => PurchaseItem::ITEM_TYPE_PRODUCT,
                'item_id' => $product->id,
                'presentation_type' => PurchaseItem::PRESENTATION_TYPE_PRODUCT,
                'presentation_id' => $productPresentation->id,
                'quantity' => 2,
                'unit_cost' => 120,
                'total' => 240,
            ],
        ],
    ]);

    $response->assertRedirect();

    $purchase = Purchase::query()->firstOrFail();

    expect($purchase->items()->count())->toBe(2);
    $this->assertDatabaseHas('purchase_items', [
        'purchase_id' => $purchase->id,
        'item_type' => PurchaseItem::ITEM_TYPE_PRODUCT,
        'item_id' => $product->id,
    ]);
    $this->assertDatabaseMissing('inventory_movements', [
        'reference_type' => Purchase::class,
        'reference_id' => $purchase->id,
    ]);
});

test('admin can update a purchase and replace its items', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create(['username' => 'purchaseadmin03']);
    $admin->assignRole('admin');

    $rawWarehouse = Warehouse::factory()->create([
        'type' => Warehouse::TYPE_RAW_MATERIAL,
        'is_active' => true,
    ]);
    $finishedWarehouse = Warehouse::factory()->create([
        'type' => Warehouse::TYPE_FINISHED_PRODUCT,
        'is_active' => true,
    ]);

    $firstSupplier = Supplier::factory()->create();
    $secondSupplier = Supplier::factory()->create();
    $rawMaterial = RawMaterial::factory()->create();
    $rawMaterialPresentation = RawMaterialPresentation::factory()->create([
        'raw_material_id' => $rawMaterial->id,
    ]);
    $product = Product::factory()->create();
    $productPresentation = ProductPresentation::factory()->create([
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    $purchase = Purchase::factory()->create([
        'supplier_id' => $firstSupplier->id,
        'status' => Purchase::STATUS_DRAFT,
    ]);

    PurchaseItem::factory()->create([
        'purchase_id' => $purchase->id,
        'item_type' => PurchaseItem::ITEM_TYPE_RAW_MATERIAL,
        'item_id' => $rawMaterial->id,
        'presentation_type' => PurchaseItem::PRESENTATION_TYPE_RAW_MATERIAL,
        'presentation_id' => $rawMaterialPresentation->id,
    ]);

    $response = $this->actingAs($admin)->patch(route('purchases.update', $purchase), [
        'supplier_id' => $secondSupplier->id,
        'status' => Purchase::STATUS_RECEIVED,
        'purchased_at' => now()->format('Y-m-d H:i:s'),
        'notes' => 'Compra recibida',
        'items' => [
            [
                'item_type' => PurchaseItem::ITEM_TYPE_PRODUCT,
                'item_id' => $product->id,
                'presentation_type' => PurchaseItem::PRESENTATION_TYPE_PRODUCT,
                'presentation_id' => $productPresentation->id,
                'quantity' => 3,
                'unit_cost' => 150,
                'total' => 450,
            ],
        ],
    ]);

    $response->assertRedirect();

    $purchase->refresh();

    expect($purchase->supplier_id)->toBe($secondSupplier->id);
    expect($purchase->status)->toBe(Purchase::STATUS_RECEIVED);
    expect($purchase->items()->count())->toBe(1);

    $this->assertDatabaseHas('purchase_items', [
        'purchase_id' => $purchase->id,
        'item_type' => PurchaseItem::ITEM_TYPE_PRODUCT,
        'item_id' => $product->id,
    ]);
    $this->assertDatabaseHas('inventory_movements', [
        'warehouse_id' => $finishedWarehouse->id,
        'item_type' => InventoryMovement::ITEM_TYPE_PRODUCT,
        'item_id' => $product->id,
        'movement_type' => InventoryMovement::TYPE_PURCHASE,
        'direction' => InventoryMovement::DIRECTION_IN,
        'quantity' => '3.000',
        'unit_cost' => '150.00',
        'reference_type' => Purchase::class,
        'reference_id' => $purchase->id,
    ]);
    $this->assertDatabaseMissing('purchase_items', [
        'purchase_id' => $purchase->id,
        'item_type' => PurchaseItem::ITEM_TYPE_RAW_MATERIAL,
        'item_id' => $rawMaterial->id,
    ]);
    $this->assertDatabaseMissing('inventory_movements', [
        'warehouse_id' => $rawWarehouse->id,
        'item_type' => InventoryMovement::ITEM_TYPE_RAW_MATERIAL,
        'item_id' => $rawMaterial->id,
        'reference_type' => Purchase::class,
        'reference_id' => $purchase->id,
    ]);
});

test('received purchases sync inventory movements and remove them when status changes', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create(['username' => 'purchaseadmin05']);
    $admin->assignRole('admin');

    $rawWarehouse = Warehouse::factory()->create([
        'type' => Warehouse::TYPE_RAW_MATERIAL,
        'is_active' => true,
    ]);

    $supplier = Supplier::factory()->create();
    $rawMaterial = RawMaterial::factory()->create();
    $presentation = RawMaterialPresentation::factory()->create([
        'raw_material_id' => $rawMaterial->id,
        'quantity' => 25,
    ]);

    $createResponse = $this->actingAs($admin)->post(route('purchases.store'), [
        'supplier_id' => $supplier->id,
        'status' => Purchase::STATUS_RECEIVED,
        'purchased_at' => now()->format('Y-m-d H:i:s'),
        'notes' => 'Compra recibida con costales',
        'items' => [
            [
                'item_type' => PurchaseItem::ITEM_TYPE_RAW_MATERIAL,
                'item_id' => $rawMaterial->id,
                'presentation_type' => PurchaseItem::PRESENTATION_TYPE_RAW_MATERIAL,
                'presentation_id' => $presentation->id,
                'quantity' => 4,
                'unit_cost' => 250,
                'total' => 1000,
            ],
        ],
    ]);

    $createResponse->assertRedirect();

    $purchase = Purchase::query()->firstOrFail();

    $this->assertDatabaseHas('inventory_movements', [
        'warehouse_id' => $rawWarehouse->id,
        'item_type' => InventoryMovement::ITEM_TYPE_RAW_MATERIAL,
        'item_id' => $rawMaterial->id,
        'movement_type' => InventoryMovement::TYPE_PURCHASE,
        'direction' => InventoryMovement::DIRECTION_IN,
        'quantity' => '100.000',
        'unit_cost' => '250.00',
        'reference_type' => Purchase::class,
        'reference_id' => $purchase->id,
    ]);

    $movementsResponse = $this->actingAs($admin)->get(route('inventory-movements.index'));

    $movementsResponse->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('inventory-movements/index')
        ->has('movements.data', 1)
        ->where('movements.data.0.reference_label', "Purchase #{$purchase->id}")
    );

    $updateResponse = $this->actingAs($admin)->patch(route('purchases.update', $purchase), [
        'supplier_id' => $supplier->id,
        'status' => Purchase::STATUS_DRAFT,
        'purchased_at' => now()->format('Y-m-d H:i:s'),
        'notes' => 'Compra regresada a borrador',
        'items' => [
            [
                'item_type' => PurchaseItem::ITEM_TYPE_RAW_MATERIAL,
                'item_id' => $rawMaterial->id,
                'presentation_type' => PurchaseItem::PRESENTATION_TYPE_RAW_MATERIAL,
                'presentation_id' => $presentation->id,
                'quantity' => 4,
                'unit_cost' => 250,
                'total' => 1000,
            ],
        ],
    ]);

    $updateResponse->assertRedirect();

    $this->assertDatabaseMissing('inventory_movements', [
        'reference_type' => Purchase::class,
        'reference_id' => $purchase->id,
    ]);
});

test('admin can delete draft purchases but not received ones', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create(['username' => 'purchaseadmin04']);
    $admin->assignRole('admin');

    $deletable = Purchase::factory()->create([
        'status' => Purchase::STATUS_DRAFT,
    ]);
    $locked = Purchase::factory()->create([
        'status' => Purchase::STATUS_RECEIVED,
    ]);

    $deleteResponse = $this->actingAs($admin)->delete(route('purchases.destroy', $deletable));
    $deleteResponse->assertRedirect(route('purchases.index'));
    $this->assertDatabaseMissing('purchases', ['id' => $deletable->id]);

    $lockedResponse = $this->actingAs($admin)->delete(route('purchases.destroy', $locked));
    $lockedResponse->assertStatus(404);
    $this->assertDatabaseHas('purchases', ['id' => $locked->id]);
});
