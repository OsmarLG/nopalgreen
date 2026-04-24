<?php

use App\Models\Product;
use App\Models\Recipe;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolesAndMasterUserSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('admin can view the products page', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    Product::factory()->count(2)->create();

    $admin = User::factory()->create([
        'username' => 'prodadmin01',
    ]);
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get(route('products.index'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('products/index')
        ->has('products.data', 2)
    );
});

test('admin can create a product with supplier origin data', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create([
        'username' => 'prodadmin02',
    ]);
    $admin->assignRole('admin');

    $unit = Unit::factory()->create();
    $supplier = Supplier::factory()->create();

    $response = $this->actingAs($admin)->post(route('products.store'), [
        'name' => 'Totopos Horneados',
        'description' => 'Producto listo para venta.',
        'base_unit_id' => $unit->id,
        'supplier_id' => $supplier->id,
        'supply_source' => Product::SUPPLY_SOURCE_MIXED,
        'product_type' => Product::TYPE_FINISHED,
        'sale_price' => 32.50,
        'is_active' => true,
    ]);

    $response->assertRedirect();

    $product = Product::query()->where('name', 'Totopos Horneados')->firstOrFail();
    $product->load('supplierLinks');

    expect($product->slug)->toBe('totopos-horneados');
    expect($product->supply_source)->toBe(Product::SUPPLY_SOURCE_MIXED);
    expect($product->sale_price)->toBe('32.50');
    expect($product->supplierLinks)->toHaveCount(1);
    expect($product->supplierLinks->first()?->supplier_id)->toBe($supplier->id);
});

test('admin can update a product and clear its supplier', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create([
        'username' => 'prodadmin03',
    ]);
    $admin->assignRole('admin');

    $unit = Unit::factory()->create();
    $supplier = Supplier::factory()->create();
    $product = Product::factory()->create([
        'name' => 'Tortilla Base',
        'slug' => 'tortilla-base',
        'base_unit_id' => $unit->id,
        'supply_source' => Product::SUPPLY_SOURCE_SUPPLIER,
        'product_type' => Product::TYPE_INTERMEDIATE,
    ]);
    $product->supplierLinks()->create([
        'supplier_id' => $supplier->id,
        'supplier_sku' => 'PR-BASE',
        'cost' => null,
        'is_primary' => true,
    ]);

    $response = $this->actingAs($admin)->patch(route('products.update', $product), [
        'name' => 'Tortilla Produccion',
        'description' => 'Producto actualizado para produccion.',
        'base_unit_id' => $unit->id,
        'supplier_id' => null,
        'supply_source' => Product::SUPPLY_SOURCE_PRODUCTION,
        'product_type' => Product::TYPE_INTERMEDIATE,
        'sale_price' => 27.90,
        'is_active' => false,
    ]);

    $response->assertRedirect();

    $product->refresh();

    expect($product->name)->toBe('Tortilla Produccion');
    expect($product->slug)->toBe('tortilla-produccion');
    expect($product->supply_source)->toBe(Product::SUPPLY_SOURCE_PRODUCTION);
    expect($product->sale_price)->toBe('27.90');
    expect($product->is_active)->toBeFalse();
    expect($product->supplierLinks()->count())->toBe(0);

    $indexResponse = $this->actingAs($admin)->get(route('products.index'));

    $indexResponse->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('products/index')
        ->where('products.data.0.sale_price', '27.90')
    );
});

test('admin can delete unused products and deactivate products already in use', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create([
        'username' => 'prodadmin04',
    ]);
    $admin->assignRole('admin');

    $unusedProduct = Product::factory()->create();
    $usedProduct = Product::factory()->create(['is_active' => true]);
    $yieldUnit = Unit::factory()->create();

    Recipe::factory()->create([
        'product_id' => $usedProduct->id,
        'yield_unit_id' => $yieldUnit->id,
    ]);

    $deleteResponse = $this->actingAs($admin)->delete(route('products.destroy', $unusedProduct));
    $deleteResponse->assertRedirect(route('products.index'));
    $this->assertDatabaseMissing('products', ['id' => $unusedProduct->id]);

    $toggleResponse = $this->actingAs($admin)->patch(route('products.toggle-active', $usedProduct));
    $toggleResponse->assertRedirect(route('products.index'));

    $usedProduct->refresh();

    expect($usedProduct->is_active)->toBeFalse();
});
