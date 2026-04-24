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

test('admin can view the pos page', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create(['username' => 'posadmin01']);
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get(route('pos.index'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('pos/index')
        ->has('products')
        ->has('saleTypes')
        ->has('statuses')
    );
});

test('admin can create a sale from pos and return to pos', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    Warehouse::factory()->create([
        'type' => Warehouse::TYPE_FINISHED_PRODUCT,
        'is_active' => true,
    ]);

    $admin = User::factory()->create(['username' => 'posadmin02']);
    $admin->assignRole('admin');

    $customer = Customer::factory()->create();
    $product = Product::factory()->create(['sale_price' => 24]);
    $presentation = ProductPresentation::factory()->create([
        'product_id' => $product->id,
    ]);

    $response = $this->actingAs($admin)->post(route('pos.store'), [
        'customer_id' => $customer->id,
        'delivery_user_id' => null,
        'sale_type' => Sale::TYPE_DIRECT,
        'status' => Sale::STATUS_COMPLETED,
        'sale_date' => now()->format('Y-m-d H:i:s'),
        'delivery_date' => null,
        'completed_at' => now()->format('Y-m-d H:i:s'),
        'notes' => 'Venta rapida POS',
        'items' => [
            [
                'product_id' => $product->id,
                'presentation_id' => $presentation->id,
                'quantity' => 2,
                'sold_quantity' => 2,
                'returned_quantity' => 0,
                'catalog_price' => 24,
                'unit_price' => 24,
                'discount_note' => null,
            ],
        ],
    ]);

    $response->assertRedirect(route('pos.index'));
    $response->assertSessionHas('status');

    $sale = Sale::query()->firstOrFail();

    expect($sale->notes)->toBe('Venta rapida POS');

    $this->assertDatabaseHas('inventory_movements', [
        'reference_type' => Sale::class,
        'reference_id' => $sale->id,
        'movement_type' => InventoryMovement::TYPE_SALE,
        'direction' => InventoryMovement::DIRECTION_OUT,
        'item_id' => $product->id,
        'quantity' => '2.000',
    ]);
});
