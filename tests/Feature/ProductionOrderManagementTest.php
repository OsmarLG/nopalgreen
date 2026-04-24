<?php

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\RawMaterial;
use App\Models\Recipe;
use App\Models\RecipeItem;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RolesAndMasterUserSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('admin can view the production orders page', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $recipe = Recipe::factory()->create();
    ProductionOrder::factory()->create([
        'recipe_id' => $recipe->id,
        'product_id' => $recipe->product_id,
        'unit_id' => $recipe->yield_unit_id,
    ]);

    $admin = User::factory()->create(['username' => 'productionadmin01']);
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get(route('production-orders.index'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('production-orders/index')
        ->has('productionOrders.data', 1)
    );
});

test('admin can create a production order from a recipe', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create(['username' => 'productionadmin02']);
    $admin->assignRole('admin');

    $recipe = Recipe::factory()->create([
        'yield_quantity' => 100,
    ]);

    $rawMaterial = RawMaterial::factory()->create();

    RecipeItem::factory()->create([
        'recipe_id' => $recipe->id,
        'item_type' => RecipeItem::ITEM_TYPE_RAW_MATERIAL,
        'item_id' => $rawMaterial->id,
        'quantity' => 80,
        'unit_id' => $recipe->yield_unit_id,
    ]);

    $response = $this->actingAs($admin)->post(route('production-orders.store'), [
        'product_id' => $recipe->product_id,
        'recipe_id' => $recipe->id,
        'planned_quantity' => 125,
        'produced_quantity' => 0,
        'unit_id' => $recipe->yield_unit_id,
        'status' => ProductionOrder::STATUS_PLANNED,
        'scheduled_for' => now()->addDay()->format('Y-m-d H:i:s'),
        'started_at' => null,
        'finished_at' => null,
        'notes' => 'Produccion del turno matutino',
        'consumptions' => [
            [
                'item_type' => RecipeItem::ITEM_TYPE_RAW_MATERIAL,
                'item_id' => $rawMaterial->id,
                'planned_quantity' => 100,
                'consumed_quantity' => 0,
                'unit_id' => $recipe->yield_unit_id,
            ],
        ],
    ]);

    $response->assertRedirect();

    $productionOrder = ProductionOrder::query()->firstOrFail();

    expect($productionOrder->product_id)->toBe($recipe->product_id);
    expect($productionOrder->consumptions()->count())->toBe(1);
    expect($productionOrder->outputs()->count())->toBe(1);

    $this->assertDatabaseHas('production_order_outputs', [
        'production_order_id' => $productionOrder->id,
        'product_id' => $recipe->product_id,
        'quantity' => '0.000',
    ]);
    $this->assertDatabaseMissing('inventory_movements', [
        'reference_type' => ProductionOrder::class,
        'reference_id' => $productionOrder->id,
    ]);
});

test('admin can update a production order and sync consumptions', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create(['username' => 'productionadmin03']);
    $admin->assignRole('admin');

    $finishedWarehouse = Warehouse::factory()->create([
        'type' => Warehouse::TYPE_FINISHED_PRODUCT,
        'is_active' => true,
    ]);

    $recipe = Recipe::factory()->create();
    $firstRawMaterial = RawMaterial::factory()->create();
    $secondProduct = Product::factory()->create();
    $rawWarehouse = Warehouse::factory()->create([
        'type' => Warehouse::TYPE_RAW_MATERIAL,
        'is_active' => true,
    ]);

    $productionOrder = ProductionOrder::factory()->create([
        'recipe_id' => $recipe->id,
        'product_id' => $recipe->product_id,
        'unit_id' => $recipe->yield_unit_id,
        'status' => ProductionOrder::STATUS_DRAFT,
    ]);

    $productionOrder->consumptions()->create([
        'item_type' => RecipeItem::ITEM_TYPE_RAW_MATERIAL,
        'item_id' => $firstRawMaterial->id,
        'planned_quantity' => 10,
        'consumed_quantity' => 0,
        'unit_id' => $recipe->yield_unit_id,
    ]);

    $response = $this->actingAs($admin)->patch(route('production-orders.update', $productionOrder), [
        'product_id' => $recipe->product_id,
        'recipe_id' => $recipe->id,
        'planned_quantity' => 140,
        'produced_quantity' => 132,
        'unit_id' => $recipe->yield_unit_id,
        'status' => ProductionOrder::STATUS_COMPLETED,
        'scheduled_for' => now()->format('Y-m-d H:i:s'),
        'started_at' => now()->subHour()->format('Y-m-d H:i:s'),
        'finished_at' => now()->format('Y-m-d H:i:s'),
        'notes' => 'Orden completada',
        'consumptions' => [
            [
                'item_type' => RecipeItem::ITEM_TYPE_PRODUCT,
                'item_id' => $secondProduct->id,
                'planned_quantity' => 140,
                'consumed_quantity' => 132,
                'unit_id' => $recipe->yield_unit_id,
            ],
        ],
    ]);

    $response->assertRedirect();

    $productionOrder->refresh();

    expect($productionOrder->status)->toBe(ProductionOrder::STATUS_COMPLETED);
    expect((string) $productionOrder->produced_quantity)->toBe('132.000');
    expect($productionOrder->consumptions()->count())->toBe(1);

    $this->assertDatabaseHas('production_order_consumptions', [
        'production_order_id' => $productionOrder->id,
        'item_type' => RecipeItem::ITEM_TYPE_PRODUCT,
        'item_id' => $secondProduct->id,
    ]);
    $this->assertDatabaseMissing('production_order_consumptions', [
        'production_order_id' => $productionOrder->id,
        'item_type' => RecipeItem::ITEM_TYPE_RAW_MATERIAL,
        'item_id' => $firstRawMaterial->id,
    ]);
    $this->assertDatabaseHas('production_order_outputs', [
        'production_order_id' => $productionOrder->id,
        'product_id' => $recipe->product_id,
        'quantity' => '132.000',
    ]);
    $this->assertDatabaseHas('inventory_movements', [
        'warehouse_id' => $finishedWarehouse->id,
        'item_type' => InventoryMovement::ITEM_TYPE_PRODUCT,
        'item_id' => $recipe->product_id,
        'movement_type' => InventoryMovement::TYPE_PRODUCTION_OUTPUT,
        'direction' => InventoryMovement::DIRECTION_IN,
        'quantity' => '132.000',
        'reference_type' => ProductionOrder::class,
        'reference_id' => $productionOrder->id,
    ]);
    $this->assertDatabaseHas('inventory_movements', [
        'warehouse_id' => $finishedWarehouse->id,
        'item_type' => InventoryMovement::ITEM_TYPE_PRODUCT,
        'item_id' => $secondProduct->id,
        'movement_type' => InventoryMovement::TYPE_PRODUCTION_CONSUMPTION,
        'direction' => InventoryMovement::DIRECTION_OUT,
        'quantity' => '132.000',
        'reference_type' => ProductionOrder::class,
        'reference_id' => $productionOrder->id,
    ]);
    $this->assertDatabaseMissing('inventory_movements', [
        'warehouse_id' => $rawWarehouse->id,
        'item_type' => InventoryMovement::ITEM_TYPE_RAW_MATERIAL,
        'item_id' => $firstRawMaterial->id,
        'reference_type' => ProductionOrder::class,
        'reference_id' => $productionOrder->id,
    ]);
});

test('completed production orders remove inventory movements when status changes back', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create(['username' => 'productionadmin05']);
    $admin->assignRole('admin');

    Warehouse::factory()->create([
        'type' => Warehouse::TYPE_RAW_MATERIAL,
        'is_active' => true,
    ]);
    Warehouse::factory()->create([
        'type' => Warehouse::TYPE_FINISHED_PRODUCT,
        'is_active' => true,
    ]);

    $recipe = Recipe::factory()->create();
    $rawMaterial = RawMaterial::factory()->create();

    $response = $this->actingAs($admin)->post(route('production-orders.store'), [
        'product_id' => $recipe->product_id,
        'recipe_id' => $recipe->id,
        'planned_quantity' => 90,
        'produced_quantity' => 84,
        'unit_id' => $recipe->yield_unit_id,
        'status' => ProductionOrder::STATUS_COMPLETED,
        'scheduled_for' => now()->subDay()->format('Y-m-d H:i:s'),
        'started_at' => now()->subHours(2)->format('Y-m-d H:i:s'),
        'finished_at' => now()->format('Y-m-d H:i:s'),
        'notes' => 'Orden terminada',
        'consumptions' => [
            [
                'item_type' => RecipeItem::ITEM_TYPE_RAW_MATERIAL,
                'item_id' => $rawMaterial->id,
                'planned_quantity' => 90,
                'consumed_quantity' => 84,
                'unit_id' => $recipe->yield_unit_id,
            ],
        ],
    ]);

    $response->assertRedirect();

    $productionOrder = ProductionOrder::query()->firstOrFail();

    $this->assertDatabaseHas('inventory_movements', [
        'reference_type' => ProductionOrder::class,
        'reference_id' => $productionOrder->id,
    ]);

    $rollbackResponse = $this->actingAs($admin)->patch(route('production-orders.update', $productionOrder), [
        'product_id' => $recipe->product_id,
        'recipe_id' => $recipe->id,
        'planned_quantity' => 90,
        'produced_quantity' => 84,
        'unit_id' => $recipe->yield_unit_id,
        'status' => ProductionOrder::STATUS_PLANNED,
        'scheduled_for' => now()->addDay()->format('Y-m-d H:i:s'),
        'started_at' => null,
        'finished_at' => null,
        'notes' => 'Regresada a planeada',
        'consumptions' => [
            [
                'item_type' => RecipeItem::ITEM_TYPE_RAW_MATERIAL,
                'item_id' => $rawMaterial->id,
                'planned_quantity' => 90,
                'consumed_quantity' => 84,
                'unit_id' => $recipe->yield_unit_id,
            ],
        ],
    ]);

    $rollbackResponse->assertRedirect();

    $this->assertDatabaseMissing('inventory_movements', [
        'reference_type' => ProductionOrder::class,
        'reference_id' => $productionOrder->id,
    ]);
});

test('admin can delete draft production orders but not completed ones', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create(['username' => 'productionadmin04']);
    $admin->assignRole('admin');

    $recipe = Recipe::factory()->create();

    $deletableOrder = ProductionOrder::factory()->create([
        'recipe_id' => $recipe->id,
        'product_id' => $recipe->product_id,
        'unit_id' => $recipe->yield_unit_id,
        'status' => ProductionOrder::STATUS_DRAFT,
    ]);

    $lockedOrder = ProductionOrder::factory()->create([
        'status' => ProductionOrder::STATUS_COMPLETED,
    ]);

    $deleteResponse = $this->actingAs($admin)->delete(route('production-orders.destroy', $deletableOrder));
    $deleteResponse->assertRedirect(route('production-orders.index'));
    $this->assertDatabaseMissing('production_orders', ['id' => $deletableOrder->id]);

    $lockedResponse = $this->actingAs($admin)->delete(route('production-orders.destroy', $lockedOrder));
    $lockedResponse->assertStatus(404);
    $this->assertDatabaseHas('production_orders', ['id' => $lockedOrder->id]);
});
