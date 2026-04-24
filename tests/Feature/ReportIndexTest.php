<?php

use App\Models\AttendanceRecord;
use App\Models\FinanceTransaction;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\ProductPresentation;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\RawMaterial;
use App\Models\Recipe;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RolesAndMasterUserSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('admin can view consolidated reports', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create(['username' => 'reports-admin']);
    $admin->assignRole('admin');

    $employee = User::factory()->create([
        'username' => 'reports-employee',
        'attendance_starts_at' => '2026-04-01',
    ]);
    $employee->assignRole('empleado');

    AttendanceRecord::factory()->create([
        'user_id' => $employee->id,
        'attendance_date' => '2026-04-20',
        'check_in_at' => '2026-04-20 08:05:00',
        'check_in_status' => AttendanceRecord::STATUS_ON_TIME,
    ]);

    $unit = Unit::factory()->create(['name' => 'Kilogramo', 'code' => 'kg']);
    $warehouse = Warehouse::factory()->create(['name' => 'Principal']);
    $product = Product::factory()->create([
        'name' => 'Tortilla Blanca',
        'base_unit_id' => $unit->id,
    ]);
    $presentation = ProductPresentation::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'name' => 'Kilogramo',
    ]);

    $sale = Sale::factory()->create([
        'folio' => 'VTA-REPORT-001',
        'sale_type' => Sale::TYPE_DIRECT,
        'status' => Sale::STATUS_COMPLETED,
        'sale_date' => '2026-04-20 10:00:00',
        'subtotal' => 120,
        'discount_total' => 10,
        'total' => 110,
    ]);

    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'presentation_id' => $presentation->id,
        'quantity' => 5,
        'sold_quantity' => 5,
        'catalog_price' => 24,
        'unit_price' => 22,
        'discount_total' => 10,
        'line_total' => 110,
    ]);

    $supplier = Supplier::factory()->create(['name' => 'Proveedor Reportes']);
    $rawMaterial = RawMaterial::factory()->create([
        'name' => 'Maiz Blanco',
        'base_unit_id' => $unit->id,
    ]);

    $purchase = Purchase::factory()->create([
        'folio' => 'COM-REPORT-001',
        'supplier_id' => $supplier->id,
        'status' => Purchase::STATUS_RECEIVED,
        'purchased_at' => '2026-04-20 09:00:00',
    ]);

    PurchaseItem::factory()->create([
        'purchase_id' => $purchase->id,
        'item_id' => $rawMaterial->id,
        'quantity' => 10,
        'unit_cost' => 30,
        'total' => 300,
    ]);

    $recipe = Recipe::factory()->create([
        'product_id' => $product->id,
        'yield_unit_id' => $unit->id,
    ]);

    ProductionOrder::factory()->create([
        'folio' => 'OP-REPORT-001',
        'product_id' => $product->id,
        'recipe_id' => $recipe->id,
        'unit_id' => $unit->id,
        'status' => ProductionOrder::STATUS_COMPLETED,
        'planned_quantity' => 20,
        'produced_quantity' => 18,
        'scheduled_for' => '2026-04-20 06:00:00',
        'started_at' => '2026-04-20 06:00:00',
        'finished_at' => '2026-04-20 07:30:00',
    ]);

    InventoryMovement::factory()->create([
        'warehouse_id' => $warehouse->id,
        'item_type' => InventoryMovement::ITEM_TYPE_PRODUCT,
        'item_id' => $product->id,
        'movement_type' => InventoryMovement::TYPE_SALE,
        'direction' => InventoryMovement::DIRECTION_OUT,
        'quantity' => 5,
        'moved_at' => '2026-04-20 10:05:00',
    ]);

    FinanceTransaction::factory()->create([
        'folio' => 'FIN-REPORT-001',
        'transaction_type' => FinanceTransaction::TYPE_INCOME,
        'direction' => FinanceTransaction::DIRECTION_IN,
        'source' => FinanceTransaction::SOURCE_SALE,
        'concept' => 'Venta VTA-REPORT-001',
        'amount' => 110,
        'status' => FinanceTransaction::STATUS_POSTED,
        'occurred_at' => '2026-04-20 10:05:00',
    ]);

    $response = $this->actingAs($admin)->get(route('reports.index', [
        'from' => '2026-04-20',
        'to' => '2026-04-20',
    ]));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('reports/index')
        ->where('filters.from', '2026-04-20')
        ->where('filters.to', '2026-04-20')
        ->where('attendance.summary.employees_count', '1')
        ->where('sales.summary.completed_count', '1')
        ->where('purchases.summary.received_count', '1')
        ->where('production.summary.completed_count', '1')
        ->where('inventory.summary.movements_count', '1')
        ->where('finances.summary.income', '110.00')
        ->has('overview', 5)
        ->has('details.employees', 1)
        ->has('details.delivery_users', 0)
        ->has('details.customers', 1)
        ->has('details.products', 1)
        ->has('sales.top_products', 1)
        ->has('purchases.top_suppliers', 1)
        ->has('production.top_products', 1)
        ->has('inventory.type_breakdown', 1)
        ->has('finances.source_breakdown', 1)
    );
});

test('employee cannot access consolidated reports', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $employee = User::factory()->create(['username' => 'reports-employee-blocked']);
    $employee->assignRole('empleado');

    $response = $this->actingAs($employee)->get(route('reports.index'));

    $response->assertForbidden();
});

test('admin can export reports to excel and printable pdf view', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create(['username' => 'reports-export-admin']);
    $admin->assignRole('admin');

    $excelResponse = $this->actingAs($admin)->get(route('reports.export-excel', [
        'from' => '2026-04-20',
        'to' => '2026-04-20',
    ]));

    $excelResponse
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.ms-excel; charset=UTF-8')
        ->assertHeader('content-disposition', 'attachment; filename="reportes-2026-04-20-2026-04-20.xls"');

    $pdfResponse = $this->actingAs($admin)->get(route('reports.export-pdf', [
        'from' => '2026-04-20',
        'to' => '2026-04-20',
    ]));

    $pdfResponse
        ->assertOk()
        ->assertSee('Imprimir / Guardar PDF')
        ->assertSee('Periodo: 2026-04-20 al 2026-04-20');
});
