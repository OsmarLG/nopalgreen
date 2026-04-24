<?php

use App\Models\Product;
use App\Models\ProductPresentation;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\RawMaterial;
use App\Models\RawMaterialPresentation;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Services\PresentationService;
use Database\Seeders\RolesAndMasterUserSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('admin can view the presentations page', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    RawMaterialPresentation::factory()->create();
    ProductPresentation::factory()->create();

    $admin = User::factory()->create([
        'username' => 'presentationadmin01',
    ]);
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get(route('presentations.index'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('presentations/index')
        ->has('presentations.data', 2)
    );
});

test('admin can create a raw material presentation', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create([
        'username' => 'presentationadmin02',
    ]);
    $admin->assignRole('admin');

    $unit = Unit::factory()->create();
    $rawMaterial = RawMaterial::factory()->create();

    $response = $this->actingAs($admin)->post(route('presentations.store'), [
        'owner_type' => PresentationService::OWNER_TYPE_RAW_MATERIAL,
        'owner_id' => $rawMaterial->id,
        'name' => 'Costal 25 kg',
        'quantity' => 25,
        'unit_id' => $unit->id,
        'barcode' => '7501234567890',
        'is_active' => true,
    ]);

    $response->assertRedirect();

    $presentation = RawMaterialPresentation::query()
        ->where('raw_material_id', $rawMaterial->id)
        ->where('name', 'Costal 25 kg')
        ->firstOrFail();

    expect((string) $presentation->quantity)->toBe('25.000');
    expect($presentation->barcode)->toBe('7501234567890');
});

test('admin can update a product presentation', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create([
        'username' => 'presentationadmin03',
    ]);
    $admin->assignRole('admin');

    $unit = Unit::factory()->create();
    $product = Product::factory()->create();
    $presentation = ProductPresentation::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'name' => 'Bolsa 200 g',
        'quantity' => 0.200,
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->patch(route('presentations.update', [
        'ownerType' => PresentationService::OWNER_TYPE_PRODUCT,
        'presentation' => $presentation->id,
    ]), [
        'owner_id' => $product->id,
        'name' => 'Bolsa 250 g',
        'quantity' => 0.250,
        'unit_id' => $unit->id,
        'barcode' => null,
        'is_active' => false,
    ]);

    $response->assertRedirect();

    $presentation->refresh();

    expect($presentation->name)->toBe('Bolsa 250 g');
    expect((string) $presentation->quantity)->toBe('0.250');
    expect($presentation->is_active)->toBeFalse();
});

test('admin can delete unused presentations and deactivate used presentations', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create([
        'username' => 'presentationadmin04',
    ]);
    $admin->assignRole('admin');

    $unusedPresentation = RawMaterialPresentation::factory()->create();
    $usedPresentation = ProductPresentation::factory()->create(['is_active' => true]);
    $supplier = Supplier::factory()->create();
    $purchase = Purchase::factory()->create(['supplier_id' => $supplier->id]);

    PurchaseItem::factory()->create([
        'purchase_id' => $purchase->id,
        'item_type' => PurchaseItem::ITEM_TYPE_PRODUCT,
        'item_id' => $usedPresentation->product_id,
        'presentation_type' => PurchaseItem::PRESENTATION_TYPE_PRODUCT,
        'presentation_id' => $usedPresentation->id,
    ]);

    $deleteResponse = $this->actingAs($admin)->delete(route('presentations.destroy', [
        'ownerType' => PresentationService::OWNER_TYPE_RAW_MATERIAL,
        'presentation' => $unusedPresentation->id,
    ]));
    $deleteResponse->assertRedirect(route('presentations.index'));
    $this->assertDatabaseMissing('raw_material_presentations', ['id' => $unusedPresentation->id]);

    $toggleResponse = $this->actingAs($admin)->patch(route('presentations.toggle-active', [
        'ownerType' => PresentationService::OWNER_TYPE_PRODUCT,
        'presentation' => $usedPresentation->id,
    ]));
    $toggleResponse->assertRedirect(route('presentations.index'));

    $usedPresentation->refresh();

    expect($usedPresentation->is_active)->toBeFalse();
});

test('edit presentation includes current inactive options', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create([
        'username' => 'presentationadmin05',
    ]);
    $admin->assignRole('admin');

    $inactiveUnit = Unit::factory()->create(['is_active' => false]);
    $inactiveRawMaterial = RawMaterial::factory()->create(['is_active' => false]);
    $presentation = RawMaterialPresentation::factory()->create([
        'raw_material_id' => $inactiveRawMaterial->id,
        'unit_id' => $inactiveUnit->id,
    ]);

    $response = $this->actingAs($admin)->get(route('presentations.edit', [
        'ownerType' => PresentationService::OWNER_TYPE_RAW_MATERIAL,
        'presentation' => $presentation->id,
    ]));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('presentations/edit')
        ->where('presentationRecord.owner_id', $inactiveRawMaterial->id)
        ->where('presentationRecord.unit.id', $inactiveUnit->id)
        ->where('rawMaterials', fn ($options) => collect($options)->pluck('id')->contains($inactiveRawMaterial->id))
        ->where('units', fn ($options) => collect($options)->pluck('id')->contains($inactiveUnit->id))
    );
});
