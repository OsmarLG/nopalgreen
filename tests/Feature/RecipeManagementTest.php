<?php

use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\RawMaterial;
use App\Models\Recipe;
use App\Models\RecipeItem;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolesAndMasterUserSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('admin can view the recipes page', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    Recipe::factory()->count(2)->create();

    $admin = User::factory()->create([
        'username' => 'recipeadmin01',
    ]);
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get(route('recipes.index'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('recipes/index')
        ->has('recipes.data', 2)
    );
});

test('admin can create a recipe with raw material and product inputs', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create([
        'username' => 'recipeadmin02',
    ]);
    $admin->assignRole('admin');

    $unit = Unit::factory()->create();
    $product = Product::factory()->create(['name' => 'Totopos Artesanales']);
    $rawMaterial = RawMaterial::factory()->create(['name' => 'Aceite Premium']);
    $intermediateProduct = Product::factory()->create(['name' => 'Tortilla Base']);

    $response = $this->actingAs($admin)->post(route('recipes.store'), [
        'product_id' => $product->id,
        'name' => 'Formula totopos artesanales',
        'version' => 1,
        'yield_quantity' => 40,
        'yield_unit_id' => $unit->id,
        'is_active' => true,
        'items' => [
            [
                'item_type' => RecipeItem::ITEM_TYPE_RAW_MATERIAL,
                'item_id' => $rawMaterial->id,
                'quantity' => 5,
                'unit_id' => $unit->id,
            ],
            [
                'item_type' => RecipeItem::ITEM_TYPE_PRODUCT,
                'item_id' => $intermediateProduct->id,
                'quantity' => 20,
                'unit_id' => $unit->id,
            ],
        ],
    ]);

    $response->assertRedirect();

    $recipe = Recipe::query()->where('name', 'Formula totopos artesanales')->firstOrFail();

    expect($recipe->items()->count())->toBe(2);
    $this->assertDatabaseHas('recipe_items', [
        'recipe_id' => $recipe->id,
        'item_type' => RecipeItem::ITEM_TYPE_RAW_MATERIAL,
        'item_id' => $rawMaterial->id,
    ]);
    $this->assertDatabaseHas('recipe_items', [
        'recipe_id' => $recipe->id,
        'item_type' => RecipeItem::ITEM_TYPE_PRODUCT,
        'item_id' => $intermediateProduct->id,
    ]);
});

test('admin can update a recipe and replace its items', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create([
        'username' => 'recipeadmin03',
    ]);
    $admin->assignRole('admin');

    $unit = Unit::factory()->create();
    $product = Product::factory()->create(['name' => 'Tortilla Blanca']);
    $firstRawMaterial = RawMaterial::factory()->create(['name' => 'Maiz Blanco']);
    $secondRawMaterial = RawMaterial::factory()->create(['name' => 'Sal Fina']);

    $recipe = Recipe::factory()->create([
        'product_id' => $product->id,
        'yield_unit_id' => $unit->id,
        'version' => 1,
    ]);

    RecipeItem::factory()->create([
        'recipe_id' => $recipe->id,
        'item_type' => RecipeItem::ITEM_TYPE_RAW_MATERIAL,
        'item_id' => $firstRawMaterial->id,
        'unit_id' => $unit->id,
    ]);

    $response = $this->actingAs($admin)->patch(route('recipes.update', $recipe), [
        'product_id' => $product->id,
        'name' => 'Formula tortilla blanca actualizada',
        'version' => 2,
        'yield_quantity' => 120,
        'yield_unit_id' => $unit->id,
        'is_active' => false,
        'items' => [
            [
                'item_type' => RecipeItem::ITEM_TYPE_RAW_MATERIAL,
                'item_id' => $secondRawMaterial->id,
                'quantity' => 3,
                'unit_id' => $unit->id,
            ],
        ],
    ]);

    $response->assertRedirect();

    $recipe->refresh();

    expect($recipe->name)->toBe('Formula tortilla blanca actualizada');
    expect($recipe->version)->toBe(2);
    expect($recipe->is_active)->toBeFalse();
    expect($recipe->items()->count())->toBe(1);

    $this->assertDatabaseHas('recipe_items', [
        'recipe_id' => $recipe->id,
        'item_id' => $secondRawMaterial->id,
    ]);
    $this->assertDatabaseMissing('recipe_items', [
        'recipe_id' => $recipe->id,
        'item_id' => $firstRawMaterial->id,
    ]);
});

test('admin can delete unused recipes and deactivate recipes already in use', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create([
        'username' => 'recipeadmin04',
    ]);
    $admin->assignRole('admin');

    $unusedRecipe = Recipe::factory()->create();
    $usedRecipe = Recipe::factory()->create(['is_active' => true]);

    ProductionOrder::factory()->create([
        'recipe_id' => $usedRecipe->id,
        'product_id' => $usedRecipe->product_id,
        'unit_id' => $usedRecipe->yield_unit_id,
    ]);

    $deleteResponse = $this->actingAs($admin)->delete(route('recipes.destroy', $unusedRecipe));
    $deleteResponse->assertRedirect(route('recipes.index'));
    $this->assertDatabaseMissing('recipes', ['id' => $unusedRecipe->id]);

    $toggleResponse = $this->actingAs($admin)->patch(route('recipes.toggle-active', $usedRecipe));
    $toggleResponse->assertRedirect(route('recipes.index'));

    $usedRecipe->refresh();

    expect($usedRecipe->is_active)->toBeFalse();
});

test('edit recipe includes current inactive product raw material and unit options', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create([
        'username' => 'recipeadmin05',
    ]);
    $admin->assignRole('admin');

    $activeUnit = Unit::factory()->create(['name' => 'Zeta Activa']);
    $inactiveUnit = Unit::factory()->create(['name' => 'Aaa Inactiva', 'is_active' => false]);
    $inactiveProduct = Product::factory()->create([
        'is_active' => false,
        'base_unit_id' => $activeUnit->id,
    ]);
    $inactiveRawMaterial = RawMaterial::factory()->create([
        'is_active' => false,
        'base_unit_id' => $activeUnit->id,
    ]);

    $recipe = Recipe::factory()->create([
        'product_id' => $inactiveProduct->id,
        'yield_unit_id' => $inactiveUnit->id,
    ]);

    RecipeItem::factory()->create([
        'recipe_id' => $recipe->id,
        'item_type' => RecipeItem::ITEM_TYPE_RAW_MATERIAL,
        'item_id' => $inactiveRawMaterial->id,
        'unit_id' => $inactiveUnit->id,
    ]);

    $response = $this->actingAs($admin)->get(route('recipes.edit', $recipe));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('recipes/edit')
        ->where('recipeRecord.product.id', $inactiveProduct->id)
        ->where('recipeRecord.yield_unit.id', $inactiveUnit->id)
        ->has('products', fn (Assert $options) => $options
            ->where('0.id', $inactiveProduct->id)
            ->etc()
        )
        ->has('rawMaterials', fn (Assert $options) => $options
            ->where('0.id', $inactiveRawMaterial->id)
            ->etc()
        )
        ->has('units', fn (Assert $options) => $options
            ->where('0.id', $inactiveUnit->id)
            ->etc()
        )
    );
});
