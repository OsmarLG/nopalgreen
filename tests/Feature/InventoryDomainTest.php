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

test('recipes support raw materials and produced products as inputs without seeding operations', function () {
    $this->seed(InventoryDomainSeeder::class);

    $totoposRecipe = Recipe::query()
        ->with('items')
        ->whereHas('product', fn ($query) => $query->where('slug', 'totopos'))
        ->firstOrFail();

    expect($totoposRecipe->items)->toHaveCount(2);
    expect($totoposRecipe->items->pluck('item_type')->all())
        ->toContain('raw_material', 'product');

    $tortillaBlanca = Product::query()->where('slug', 'tortilla-blanca')->firstOrFail();
    $aceite = RawMaterial::query()->where('slug', 'aceite')->firstOrFail();

    $this->assertDatabaseHas('recipe_items', [
        'recipe_id' => $totoposRecipe->id,
        'item_type' => 'product',
        'item_id' => $tortillaBlanca->id,
    ]);
    $this->assertDatabaseHas('recipe_items', [
        'recipe_id' => $totoposRecipe->id,
        'item_type' => 'raw_material',
        'item_id' => $aceite->id,
    ]);

    expect(InventoryMovement::query()->count())->toBe(0);
    expect(FinanceTransaction::query()->count())->toBe(0);
    expect(Purchase::query()->count())->toBe(0);
    expect(ProductionOrder::query()->count())->toBe(0);
});
