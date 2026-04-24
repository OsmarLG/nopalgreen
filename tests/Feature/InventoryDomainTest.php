<?php

use App\Models\FinanceTransaction;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\Purchase;
use App\Models\RawMaterial;
use App\Models\Recipe;
use Database\Seeders\InventoryDomainSeeder;

test('inventory domain seeder creates foundational catalog data', function () {
    $this->seed(InventoryDomainSeeder::class);

    $this->assertDatabaseCount('units', 6);
    $this->assertDatabaseHas('warehouses', ['code' => 'MAT-001']);
    $this->assertDatabaseHas('raw_materials', ['slug' => 'maiz-blanco']);
    $this->assertDatabaseHas('products', ['slug' => 'tortilla-blanca']);
    $this->assertDatabaseHas('products', ['slug' => 'totopos']);
});

test('recipes and operational records support raw materials and produced products as inputs', function () {
    $this->seed(InventoryDomainSeeder::class);

    $totoposRecipe = Recipe::query()
        ->with('items')
        ->whereHas('product', fn ($query) => $query->where('slug', 'totopos'))
        ->firstOrFail();

    expect($totoposRecipe->items)->toHaveCount(2);
    expect($totoposRecipe->items->pluck('item_type')->all())
        ->toContain('raw_material', 'product');

    $tortillaBlanca = Product::query()->where('slug', 'tortilla-blanca')->firstOrFail();
    $maizBlanco = RawMaterial::query()->where('slug', 'maiz-blanco')->firstOrFail();
    $purchase = Purchase::query()->firstOrFail();
    $order = ProductionOrder::query()->firstOrFail();

    $this->assertDatabaseHas('recipe_items', [
        'recipe_id' => $totoposRecipe->id,
        'item_type' => 'product',
        'item_id' => $tortillaBlanca->id,
    ]);

    $this->assertDatabaseHas('inventory_movements', [
        'item_type' => InventoryMovement::ITEM_TYPE_RAW_MATERIAL,
        'item_id' => $maizBlanco->id,
        'movement_type' => InventoryMovement::TYPE_PURCHASE,
        'reference_type' => Purchase::class,
        'reference_id' => $purchase->id,
    ]);

    $this->assertDatabaseHas('inventory_movements', [
        'item_type' => InventoryMovement::ITEM_TYPE_PRODUCT,
        'item_id' => $order->product_id,
        'movement_type' => InventoryMovement::TYPE_PRODUCTION_OUTPUT,
        'reference_type' => ProductionOrder::class,
        'reference_id' => $order->id,
    ]);

    $this->assertDatabaseHas('finance_transactions', [
        'reference_type' => Purchase::class,
        'reference_id' => $purchase->id,
        'transaction_type' => FinanceTransaction::TYPE_EXPENSE,
        'source' => FinanceTransaction::SOURCE_PURCHASE,
    ]);

    $this->assertDatabaseHas('finance_transactions', [
        'reference_type' => ProductionOrder::class,
        'reference_id' => $order->id,
        'transaction_type' => FinanceTransaction::TYPE_EXPENSE,
        'source' => FinanceTransaction::SOURCE_PRODUCTION,
    ]);
});
