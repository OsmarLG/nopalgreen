<?php

use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductPresentation;
use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RolesAndMasterUserSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('admin can view the sales page', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    Sale::factory()->create();

    $admin = User::factory()->create(['username' => 'salesadmin01']);
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get(route('sales.index'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('sales/index')
        ->has('sales.data', 1)
    );
});

test('admin can create a completed direct sale and sync inventory output', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    Warehouse::factory()->create([
        'type' => Warehouse::TYPE_FINISHED_PRODUCT,
        'is_active' => true,
    ]);

    $admin = User::factory()->create(['username' => 'salesadmin02']);
    $admin->assignRole('admin');

    $customer = Customer::factory()->create();
    $deliveryUser = User::factory()->create(['username' => 'repartidor-directo']);
    $deliveryUser->assignRole('repartidor');
    $product = Product::factory()->create(['sale_price' => 20]);
    $presentation = ProductPresentation::factory()->create([
        'product_id' => $product->id,
    ]);

    $response = $this->actingAs($admin)->post(route('sales.store'), [
        'customer_id' => $customer->id,
        'delivery_user_id' => $deliveryUser->id,
        'sale_type' => Sale::TYPE_DIRECT,
        'status' => Sale::STATUS_COMPLETED,
        'sale_date' => now()->format('Y-m-d H:i:s'),
        'delivery_date' => now()->addDay()->format('Y-m-d H:i:s'),
        'completed_at' => now()->format('Y-m-d H:i:s'),
        'notes' => 'Venta mostrador',
        'items' => [
            [
                'product_id' => $product->id,
                'presentation_id' => $presentation->id,
                'quantity' => 10,
                'sold_quantity' => 4,
                'returned_quantity' => 6,
                'catalog_price' => 20,
                'unit_price' => 18,
                'discount_note' => 'Precio especial',
            ],
        ],
    ]);

    $response->assertRedirect();

    $sale = Sale::query()->firstOrFail();

    expect($sale->total)->toBe('180.00');
    expect($sale->discount_total)->toBe('20.00');
    expect($sale->delivery_user_id)->toBeNull();
    expect($sale->delivery_date)->toBeNull();
    expect($sale->items()->firstOrFail()->sold_quantity)->toBe('10.000');
    expect($sale->items()->firstOrFail()->returned_quantity)->toBe('0.000');

    $this->assertDatabaseHas('inventory_movements', [
        'reference_type' => Sale::class,
        'reference_id' => $sale->id,
        'movement_type' => InventoryMovement::TYPE_SALE,
        'direction' => InventoryMovement::DIRECTION_OUT,
        'item_id' => $product->id,
        'quantity' => '10.000',
    ]);
});

test('delivery sales require a customer and delivery user and sync dispatch plus return on completion', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    Warehouse::factory()->create([
        'type' => Warehouse::TYPE_FINISHED_PRODUCT,
        'is_active' => true,
    ]);

    $admin = User::factory()->create(['username' => 'salesadmin03']);
    $admin->assignRole('admin');

    $deliveryUser = User::factory()->create(['username' => 'repartidor01']);
    $deliveryUser->assignRole('repartidor');

    $customer = Customer::factory()->create();
    $product = Product::factory()->create(['sale_price' => 25]);
    $presentation = ProductPresentation::factory()->create([
        'product_id' => $product->id,
    ]);

    $invalidResponse = $this->actingAs($admin)->post(route('sales.store'), [
        'customer_id' => null,
        'delivery_user_id' => null,
        'sale_type' => Sale::TYPE_DELIVERY,
        'status' => Sale::STATUS_ASSIGNED,
        'sale_date' => now()->format('Y-m-d H:i:s'),
        'delivery_date' => now()->addDay()->format('Y-m-d H:i:s'),
        'completed_at' => null,
        'notes' => 'Salida a reparto',
        'items' => [
            [
                'product_id' => $product->id,
                'presentation_id' => $presentation->id,
                'quantity' => 12,
                'sold_quantity' => 0,
                'returned_quantity' => 0,
                'catalog_price' => 25,
                'unit_price' => 25,
                'discount_note' => null,
            ],
        ],
    ]);

    $invalidResponse->assertSessionHasErrors(['customer_id', 'delivery_user_id']);

    $createResponse = $this->actingAs($admin)->post(route('sales.store'), [
        'customer_id' => $customer->id,
        'delivery_user_id' => $deliveryUser->id,
        'sale_type' => Sale::TYPE_DELIVERY,
        'status' => Sale::STATUS_ASSIGNED,
        'sale_date' => now()->format('Y-m-d H:i:s'),
        'delivery_date' => now()->addDay()->format('Y-m-d H:i:s'),
        'completed_at' => null,
        'notes' => 'Salida a reparto',
        'items' => [
            [
                'product_id' => $product->id,
                'presentation_id' => $presentation->id,
                'quantity' => 12,
                'sold_quantity' => 0,
                'returned_quantity' => 0,
                'catalog_price' => 25,
                'unit_price' => 25,
                'discount_note' => null,
            ],
        ],
    ]);

    $createResponse->assertRedirect();

    $sale = Sale::query()->firstOrFail();

    $this->assertDatabaseHas('inventory_movements', [
        'reference_type' => Sale::class,
        'reference_id' => $sale->id,
        'movement_type' => InventoryMovement::TYPE_SALE_DISPATCH,
        'direction' => InventoryMovement::DIRECTION_OUT,
        'item_id' => $product->id,
        'quantity' => '12.000',
    ]);

    $updateResponse = $this->actingAs($admin)->patch(route('sales.update', $sale), [
        'customer_id' => $customer->id,
        'delivery_user_id' => $deliveryUser->id,
        'sale_type' => Sale::TYPE_DELIVERY,
        'status' => Sale::STATUS_COMPLETED,
        'sale_date' => now()->format('Y-m-d H:i:s'),
        'delivery_date' => now()->addDay()->format('Y-m-d H:i:s'),
        'completed_at' => now()->addDay()->format('Y-m-d H:i:s'),
        'notes' => 'Liquidacion completa',
        'items' => [
            [
                'product_id' => $product->id,
                'presentation_id' => $presentation->id,
                'quantity' => 12,
                'sold_quantity' => 9,
                'returned_quantity' => 3,
                'catalog_price' => 25,
                'unit_price' => 22,
                'discount_note' => 'Cliente mayorista',
            ],
        ],
    ]);

    $updateResponse->assertRedirect();

    $sale->refresh();

    expect($sale->total)->toBe('198.00');
    expect($sale->discount_total)->toBe('27.00');
    expect($sale->delivery_user_id)->toBe($deliveryUser->id);
    expect($sale->delivery_date)->not->toBeNull();
    expect($sale->items()->firstOrFail()->sold_quantity)->toBe('9.000');
    expect($sale->items()->firstOrFail()->returned_quantity)->toBe('3.000');

    $this->assertDatabaseHas('inventory_movements', [
        'reference_type' => Sale::class,
        'reference_id' => $sale->id,
        'movement_type' => InventoryMovement::TYPE_SALE_DISPATCH,
        'direction' => InventoryMovement::DIRECTION_OUT,
        'item_id' => $product->id,
        'quantity' => '12.000',
    ]);
    $this->assertDatabaseHas('inventory_movements', [
        'reference_type' => Sale::class,
        'reference_id' => $sale->id,
        'movement_type' => InventoryMovement::TYPE_RETURN,
        'direction' => InventoryMovement::DIRECTION_IN,
        'item_id' => $product->id,
        'quantity' => '3.000',
    ]);
});

test('admin can delete draft sales but not completed ones', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create(['username' => 'salesadmin04']);
    $admin->assignRole('admin');

    $draftSale = Sale::factory()->create([
        'status' => Sale::STATUS_DRAFT,
    ]);
    $completedSale = Sale::factory()->create([
        'status' => Sale::STATUS_COMPLETED,
    ]);

    $deleteResponse = $this->actingAs($admin)->delete(route('sales.destroy', $draftSale));
    $deleteResponse->assertRedirect(route('sales.index'));
    $this->assertDatabaseMissing('sales', ['id' => $draftSale->id]);

    $blockedResponse = $this->actingAs($admin)->delete(route('sales.destroy', $completedSale));
    $blockedResponse->assertNotFound();
    $this->assertDatabaseHas('sales', ['id' => $completedSale->id]);
});
