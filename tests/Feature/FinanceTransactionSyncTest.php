<?php

use App\Models\FinanceTransaction;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\ProductPresentation;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\RawMaterial;
use App\Models\RawMaterialPresentation;
use App\Models\Recipe;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RolesAndMasterUserSeeder;

test('received purchases create automatic expense finance entries', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    Warehouse::factory()->create([
        'type' => Warehouse::TYPE_RAW_MATERIAL,
        'is_active' => true,
    ]);

    $admin = User::factory()->create(['username' => 'financeautosync01']);
    $admin->assignRole('admin');

    $supplier = Supplier::factory()->create(['name' => 'Harinas del Norte']);
    $rawMaterial = RawMaterial::factory()->create();
    $presentation = RawMaterialPresentation::factory()->create([
        'raw_material_id' => $rawMaterial->id,
        'quantity' => 25,
    ]);

    $response = $this->actingAs($admin)->post(route('purchases.store'), [
        'supplier_id' => $supplier->id,
        'status' => Purchase::STATUS_RECEIVED,
        'purchased_at' => now()->format('Y-m-d H:i:s'),
        'notes' => 'Compra semanal',
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

    $response->assertRedirect();

    $purchase = Purchase::query()->firstOrFail();

    $this->assertDatabaseHas('finance_transactions', [
        'reference_type' => Purchase::class,
        'reference_id' => $purchase->id,
        'transaction_type' => FinanceTransaction::TYPE_EXPENSE,
        'source' => FinanceTransaction::SOURCE_PURCHASE,
        'amount' => '1000.00',
        'status' => FinanceTransaction::STATUS_POSTED,
        'is_manual' => false,
    ]);
});

test('completed sales create automatic income finance entries', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    Warehouse::factory()->create([
        'type' => Warehouse::TYPE_FINISHED_PRODUCT,
        'is_active' => true,
    ]);

    $admin = User::factory()->create(['username' => 'financeautosync02']);
    $admin->assignRole('admin');

    $product = Product::factory()->create(['sale_price' => 20]);
    $presentation = ProductPresentation::factory()->create([
        'product_id' => $product->id,
    ]);

    $response = $this->actingAs($admin)->post(route('sales.store'), [
        'customer_id' => null,
        'delivery_user_id' => null,
        'sale_type' => Sale::TYPE_DIRECT,
        'status' => Sale::STATUS_COMPLETED,
        'sale_date' => now()->format('Y-m-d H:i:s'),
        'delivery_date' => null,
        'completed_at' => now()->format('Y-m-d H:i:s'),
        'notes' => 'Venta mostrador',
        'items' => [
            [
                'product_id' => $product->id,
                'presentation_id' => $presentation->id,
                'quantity' => 5,
                'sold_quantity' => 5,
                'returned_quantity' => 0,
                'catalog_price' => 20,
                'unit_price' => 18,
                'discount_note' => 'Promo',
            ],
        ],
    ]);

    $response->assertRedirect();

    $sale = Sale::query()->firstOrFail();

    $this->assertDatabaseHas('finance_transactions', [
        'reference_type' => Sale::class,
        'reference_id' => $sale->id,
        'transaction_type' => FinanceTransaction::TYPE_INCOME,
        'source' => FinanceTransaction::SOURCE_SALE,
        'amount' => '90.00',
        'status' => FinanceTransaction::STATUS_POSTED,
        'is_manual' => false,
    ]);
});

test('completed production orders create automatic production expense entries', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $rawWarehouse = Warehouse::factory()->create([
        'type' => Warehouse::TYPE_RAW_MATERIAL,
        'is_active' => true,
    ]);
    Warehouse::factory()->create([
        'type' => Warehouse::TYPE_FINISHED_PRODUCT,
        'is_active' => true,
    ]);

    $admin = User::factory()->create(['username' => 'financeautosync03']);
    $admin->assignRole('admin');

    $recipe = Recipe::factory()->create();
    $rawMaterial = RawMaterial::factory()->create();

    InventoryMovement::factory()->create([
        'warehouse_id' => $rawWarehouse->id,
        'item_type' => InventoryMovement::ITEM_TYPE_RAW_MATERIAL,
        'item_id' => $rawMaterial->id,
        'movement_type' => InventoryMovement::TYPE_PURCHASE,
        'direction' => InventoryMovement::DIRECTION_IN,
        'quantity' => 100,
        'unit_cost' => 12.5,
        'moved_at' => now()->subDay(),
    ]);

    $response = $this->actingAs($admin)->post(route('production-orders.store'), [
        'product_id' => $recipe->product_id,
        'recipe_id' => $recipe->id,
        'planned_quantity' => 80,
        'produced_quantity' => 75,
        'unit_id' => $recipe->yield_unit_id,
        'status' => ProductionOrder::STATUS_COMPLETED,
        'scheduled_for' => now()->subDay()->format('Y-m-d H:i:s'),
        'started_at' => now()->subHours(2)->format('Y-m-d H:i:s'),
        'finished_at' => now()->format('Y-m-d H:i:s'),
        'notes' => 'Lote terminado',
        'consumptions' => [
            [
                'item_type' => InventoryMovement::ITEM_TYPE_RAW_MATERIAL,
                'item_id' => $rawMaterial->id,
                'planned_quantity' => 80,
                'consumed_quantity' => 75,
                'unit_id' => $recipe->yield_unit_id,
            ],
        ],
    ]);

    $response->assertRedirect();

    $productionOrder = ProductionOrder::query()->firstOrFail();

    $this->assertDatabaseHas('finance_transactions', [
        'reference_type' => ProductionOrder::class,
        'reference_id' => $productionOrder->id,
        'transaction_type' => FinanceTransaction::TYPE_EXPENSE,
        'source' => FinanceTransaction::SOURCE_PRODUCTION,
        'amount' => '937.50',
        'status' => FinanceTransaction::STATUS_POSTED,
        'is_manual' => false,
    ]);
});

test('waste adjustments create automatic loss finance entries', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create(['username' => 'financeautosync04']);
    $admin->assignRole('admin');

    $warehouse = Warehouse::factory()->create();
    $product = Product::factory()->create();

    $response = $this->actingAs($admin)->post(route('inventory-adjustments.store'), [
        'warehouse_id' => $warehouse->id,
        'item_type' => InventoryMovement::ITEM_TYPE_PRODUCT,
        'item_id' => $product->id,
        'movement_type' => InventoryMovement::TYPE_WASTE,
        'direction' => InventoryMovement::DIRECTION_OUT,
        'quantity' => 3,
        'unit_cost' => 42.5,
        'moved_at' => now()->format('Y-m-d H:i:s'),
        'notes' => 'Producto maltratado',
    ]);

    $response->assertRedirect();

    $movement = InventoryMovement::query()->where('movement_type', InventoryMovement::TYPE_WASTE)->firstOrFail();

    $this->assertDatabaseHas('finance_transactions', [
        'reference_type' => InventoryMovement::class,
        'reference_id' => $movement->id,
        'transaction_type' => FinanceTransaction::TYPE_LOSS,
        'source' => FinanceTransaction::SOURCE_WASTE,
        'amount' => '127.50',
        'status' => FinanceTransaction::STATUS_POSTED,
        'is_manual' => false,
    ]);
});
