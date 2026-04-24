<?php

use App\Http\Controllers\AttendanceMarkController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentationController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\InventoryAdjustmentController;
use App\Http\Controllers\InventoryMovementController;
use App\Http\Controllers\InventoryStockController;
use App\Http\Controllers\InventoryTransferController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PresentationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductionOrderController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\RawMaterialController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\Settings\AttendanceSettingsController;
use App\Http\Controllers\Settings\BrandingController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    if (auth()->check()) {
        return to_route('dashboard');
    }

    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('documentation', [DocumentationController::class, 'index'])->name('documentation.index');
    Route::get('documentation/{slug}', [DocumentationController::class, 'show'])->name('documentation.show');
    Route::get('branding', [BrandingController::class, 'edit'])
        ->middleware('can:branding.update')
        ->name('branding.edit');
    Route::post('branding', [BrandingController::class, 'update'])
        ->middleware('can:branding.update')
        ->name('branding.update');
    Route::get('attendance-settings', [AttendanceSettingsController::class, 'edit'])
        ->middleware('can:attendance.manage')
        ->name('attendance-settings.edit');
    Route::patch('attendance-settings', [AttendanceSettingsController::class, 'update'])
        ->middleware('can:attendance.manage')
        ->name('attendance-settings.update');
    Route::get('attendance-mark', [AttendanceMarkController::class, 'edit'])
        ->middleware('can:attendance.mark')
        ->name('attendance-mark.edit');
    Route::post('attendance-mark', [AttendanceMarkController::class, 'store'])
        ->middleware('can:attendance.mark')
        ->name('attendance-mark.store');
    Route::get('employees', [EmployeeController::class, 'index'])
        ->middleware('can:employees.view')
        ->name('employees.index');
    Route::get('employees/{employee}', [EmployeeController::class, 'show'])
        ->middleware('can:employees.view')
        ->name('employees.show');
    Route::get('finances', [FinanceController::class, 'index'])
        ->middleware('can:finances.view')
        ->name('finances.index');
    Route::get('reports', [ReportController::class, 'index'])
        ->middleware('can:reports.view')
        ->name('reports.index');
    Route::get('reports/export/excel', [ReportController::class, 'exportExcel'])
        ->middleware('can:reports.view')
        ->name('reports.export-excel');
    Route::get('reports/export/pdf', [ReportController::class, 'exportPdf'])
        ->middleware('can:reports.view')
        ->name('reports.export-pdf');
    Route::get('finances/create', [FinanceController::class, 'create'])
        ->middleware('can:finances.create')
        ->name('finances.create');
    Route::post('finances', [FinanceController::class, 'store'])
        ->middleware('can:finances.create')
        ->name('finances.store');
    Route::get('finances/{finance}/edit', [FinanceController::class, 'edit'])
        ->middleware('can:finances.update')
        ->name('finances.edit');
    Route::patch('finances/{finance}', [FinanceController::class, 'update'])
        ->middleware('can:finances.update')
        ->name('finances.update');
    Route::delete('finances/{finance}', [FinanceController::class, 'destroy'])
        ->middleware('can:finances.delete')
        ->name('finances.destroy');

    Route::get('users', [UserController::class, 'index'])
        ->middleware('can:users.view')
        ->name('users.index');
    Route::get('users/create', [UserController::class, 'create'])
        ->middleware('can:users.create')
        ->name('users.create');
    Route::post('users', [UserController::class, 'store'])
        ->middleware('can:users.create')
        ->name('users.store');
    Route::get('users/{user}/edit', [UserController::class, 'edit'])
        ->middleware('can:users.update')
        ->name('users.edit');
    Route::patch('users/{user}', [UserController::class, 'update'])
        ->middleware('can:users.update')
        ->name('users.update');
    Route::delete('users/{user}', [UserController::class, 'destroy'])
        ->middleware('can:users.delete')
        ->name('users.destroy');

    Route::get('roles', [RoleController::class, 'index'])
        ->middleware('can:roles.view')
        ->name('roles.index');
    Route::get('roles/create', [RoleController::class, 'create'])
        ->middleware('can:roles.create')
        ->name('roles.create');
    Route::post('roles', [RoleController::class, 'store'])
        ->middleware('can:roles.create')
        ->name('roles.store');
    Route::get('roles/{role}/edit', [RoleController::class, 'edit'])
        ->middleware('can:roles.view')
        ->name('roles.edit');
    Route::patch('roles/{role}', [RoleController::class, 'update'])
        ->middleware('can:roles.update')
        ->name('roles.update');

    Route::get('permissions', [PermissionController::class, 'index'])
        ->middleware('can:permissions.view')
        ->name('permissions.index');

    Route::get('units', [UnitController::class, 'index'])
        ->middleware('can:units.view')
        ->name('units.index');
    Route::get('units/create', [UnitController::class, 'create'])
        ->middleware('can:units.create')
        ->name('units.create');
    Route::post('units', [UnitController::class, 'store'])
        ->middleware('can:units.create')
        ->name('units.store');
    Route::get('units/{unit}/edit', [UnitController::class, 'edit'])
        ->middleware('can:units.update')
        ->name('units.edit');
    Route::patch('units/{unit}', [UnitController::class, 'update'])
        ->middleware('can:units.update')
        ->name('units.update');
    Route::patch('units/{unit}/toggle-active', [UnitController::class, 'toggleActive'])
        ->middleware('can:units.update')
        ->name('units.toggle-active');
    Route::delete('units/{unit}', [UnitController::class, 'destroy'])
        ->middleware('can:units.delete')
        ->name('units.destroy');

    Route::get('suppliers', [SupplierController::class, 'index'])
        ->middleware('can:suppliers.view')
        ->name('suppliers.index');
    Route::get('suppliers/create', [SupplierController::class, 'create'])
        ->middleware('can:suppliers.create')
        ->name('suppliers.create');
    Route::post('suppliers', [SupplierController::class, 'store'])
        ->middleware('can:suppliers.create')
        ->name('suppliers.store');
    Route::get('suppliers/{supplier}/edit', [SupplierController::class, 'edit'])
        ->middleware('can:suppliers.update')
        ->name('suppliers.edit');
    Route::patch('suppliers/{supplier}', [SupplierController::class, 'update'])
        ->middleware('can:suppliers.update')
        ->name('suppliers.update');
    Route::patch('suppliers/{supplier}/toggle-active', [SupplierController::class, 'toggleActive'])
        ->middleware('can:suppliers.update')
        ->name('suppliers.toggle-active');
    Route::delete('suppliers/{supplier}', [SupplierController::class, 'destroy'])
        ->middleware('can:suppliers.delete')
        ->name('suppliers.destroy');

    Route::get('customers', [CustomerController::class, 'index'])
        ->middleware('can:customers.view')
        ->name('customers.index');
    Route::get('customers/create', [CustomerController::class, 'create'])
        ->middleware('can:customers.create')
        ->name('customers.create');
    Route::post('customers', [CustomerController::class, 'store'])
        ->middleware('can:customers.create')
        ->name('customers.store');
    Route::get('customers/{customer}/edit', [CustomerController::class, 'edit'])
        ->middleware('can:customers.update')
        ->name('customers.edit');
    Route::patch('customers/{customer}', [CustomerController::class, 'update'])
        ->middleware('can:customers.update')
        ->name('customers.update');
    Route::patch('customers/{customer}/toggle-active', [CustomerController::class, 'toggleActive'])
        ->middleware('can:customers.update')
        ->name('customers.toggle-active');
    Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])
        ->middleware('can:customers.delete')
        ->name('customers.destroy');

    Route::get('sales', [SaleController::class, 'index'])
        ->middleware('can:sales.view')
        ->name('sales.index');
    Route::get('pos', [SaleController::class, 'pos'])
        ->middleware('can:sales.create')
        ->name('pos.index');
    Route::post('pos/sales', [SaleController::class, 'storeFromPos'])
        ->middleware('can:sales.create')
        ->name('pos.store');
    Route::get('sales/create', [SaleController::class, 'create'])
        ->middleware('can:sales.create')
        ->name('sales.create');
    Route::post('sales', [SaleController::class, 'store'])
        ->middleware('can:sales.create')
        ->name('sales.store');
    Route::get('sales/{sale}/edit', [SaleController::class, 'edit'])
        ->middleware('can:sales.update')
        ->name('sales.edit');
    Route::patch('sales/{sale}', [SaleController::class, 'update'])
        ->middleware('can:sales.update')
        ->name('sales.update');
    Route::delete('sales/{sale}', [SaleController::class, 'destroy'])
        ->middleware('can:sales.delete')
        ->name('sales.destroy');

    Route::get('raw-materials', [RawMaterialController::class, 'index'])
        ->middleware('can:raw_materials.view')
        ->name('raw-materials.index');
    Route::get('raw-materials/create', [RawMaterialController::class, 'create'])
        ->middleware('can:raw_materials.create')
        ->name('raw-materials.create');
    Route::post('raw-materials', [RawMaterialController::class, 'store'])
        ->middleware('can:raw_materials.create')
        ->name('raw-materials.store');
    Route::get('raw-materials/{raw_material}/edit', [RawMaterialController::class, 'edit'])
        ->middleware('can:raw_materials.update')
        ->name('raw-materials.edit');
    Route::patch('raw-materials/{raw_material}', [RawMaterialController::class, 'update'])
        ->middleware('can:raw_materials.update')
        ->name('raw-materials.update');
    Route::patch('raw-materials/{raw_material}/toggle-active', [RawMaterialController::class, 'toggleActive'])
        ->middleware('can:raw_materials.update')
        ->name('raw-materials.toggle-active');
    Route::delete('raw-materials/{raw_material}', [RawMaterialController::class, 'destroy'])
        ->middleware('can:raw_materials.delete')
        ->name('raw-materials.destroy');

    Route::get('products', [ProductController::class, 'index'])
        ->middleware('can:products.view')
        ->name('products.index');
    Route::get('products/create', [ProductController::class, 'create'])
        ->middleware('can:products.create')
        ->name('products.create');
    Route::post('products', [ProductController::class, 'store'])
        ->middleware('can:products.create')
        ->name('products.store');
    Route::get('products/{product}/edit', [ProductController::class, 'edit'])
        ->middleware('can:products.update')
        ->name('products.edit');
    Route::patch('products/{product}', [ProductController::class, 'update'])
        ->middleware('can:products.update')
        ->name('products.update');
    Route::patch('products/{product}/toggle-active', [ProductController::class, 'toggleActive'])
        ->middleware('can:products.update')
        ->name('products.toggle-active');
    Route::delete('products/{product}', [ProductController::class, 'destroy'])
        ->middleware('can:products.delete')
        ->name('products.destroy');

    Route::get('presentations', [PresentationController::class, 'index'])
        ->middleware('can:presentations.view')
        ->name('presentations.index');
    Route::get('presentations/create', [PresentationController::class, 'create'])
        ->middleware('can:presentations.create')
        ->name('presentations.create');
    Route::post('presentations', [PresentationController::class, 'store'])
        ->middleware('can:presentations.create')
        ->name('presentations.store');
    Route::get('presentations/{ownerType}/{presentation}/edit', [PresentationController::class, 'edit'])
        ->middleware('can:presentations.update')
        ->name('presentations.edit');
    Route::patch('presentations/{ownerType}/{presentation}', [PresentationController::class, 'update'])
        ->middleware('can:presentations.update')
        ->name('presentations.update');
    Route::patch('presentations/{ownerType}/{presentation}/toggle-active', [PresentationController::class, 'toggleActive'])
        ->middleware('can:presentations.update')
        ->name('presentations.toggle-active');
    Route::delete('presentations/{ownerType}/{presentation}', [PresentationController::class, 'destroy'])
        ->middleware('can:presentations.delete')
        ->name('presentations.destroy');

    Route::get('recipes', [RecipeController::class, 'index'])
        ->middleware('can:recipes.view')
        ->name('recipes.index');
    Route::get('recipes/create', [RecipeController::class, 'create'])
        ->middleware('can:recipes.create')
        ->name('recipes.create');
    Route::post('recipes', [RecipeController::class, 'store'])
        ->middleware('can:recipes.create')
        ->name('recipes.store');
    Route::get('recipes/{recipe}/edit', [RecipeController::class, 'edit'])
        ->middleware('can:recipes.update')
        ->name('recipes.edit');
    Route::patch('recipes/{recipe}', [RecipeController::class, 'update'])
        ->middleware('can:recipes.update')
        ->name('recipes.update');
    Route::patch('recipes/{recipe}/toggle-active', [RecipeController::class, 'toggleActive'])
        ->middleware('can:recipes.update')
        ->name('recipes.toggle-active');
    Route::delete('recipes/{recipe}', [RecipeController::class, 'destroy'])
        ->middleware('can:recipes.delete')
        ->name('recipes.destroy');

    Route::get('production-orders', [ProductionOrderController::class, 'index'])
        ->middleware('can:production_orders.view')
        ->name('production-orders.index');
    Route::get('production-orders/create', [ProductionOrderController::class, 'create'])
        ->middleware('can:production_orders.create')
        ->name('production-orders.create');
    Route::post('production-orders', [ProductionOrderController::class, 'store'])
        ->middleware('can:production_orders.create')
        ->name('production-orders.store');
    Route::get('production-orders/{production_order}/edit', [ProductionOrderController::class, 'edit'])
        ->middleware('can:production_orders.update')
        ->name('production-orders.edit');
    Route::patch('production-orders/{production_order}', [ProductionOrderController::class, 'update'])
        ->middleware('can:production_orders.update')
        ->name('production-orders.update');
    Route::delete('production-orders/{production_order}', [ProductionOrderController::class, 'destroy'])
        ->middleware('can:production_orders.delete')
        ->name('production-orders.destroy');

    Route::get('purchases', [PurchaseController::class, 'index'])
        ->middleware('can:purchases.view')
        ->name('purchases.index');
    Route::get('purchases/create', [PurchaseController::class, 'create'])
        ->middleware('can:purchases.create')
        ->name('purchases.create');
    Route::post('purchases', [PurchaseController::class, 'store'])
        ->middleware('can:purchases.create')
        ->name('purchases.store');
    Route::get('purchases/{purchase}/edit', [PurchaseController::class, 'edit'])
        ->middleware('can:purchases.update')
        ->name('purchases.edit');
    Route::patch('purchases/{purchase}', [PurchaseController::class, 'update'])
        ->middleware('can:purchases.update')
        ->name('purchases.update');
    Route::delete('purchases/{purchase}', [PurchaseController::class, 'destroy'])
        ->middleware('can:purchases.delete')
        ->name('purchases.destroy');

    Route::get('inventory-movements', [InventoryMovementController::class, 'index'])
        ->middleware('can:inventory_movements.view')
        ->name('inventory-movements.index');

    Route::get('inventory-stocks', [InventoryStockController::class, 'index'])
        ->middleware('can:inventory_movements.view')
        ->name('inventory-stocks.index');

    Route::get('inventory-adjustments', [InventoryAdjustmentController::class, 'index'])
        ->middleware('can:inventory_adjustments.view')
        ->name('inventory-adjustments.index');
    Route::get('inventory-adjustments/create', [InventoryAdjustmentController::class, 'create'])
        ->middleware('can:inventory_adjustments.create')
        ->name('inventory-adjustments.create');
    Route::post('inventory-adjustments', [InventoryAdjustmentController::class, 'store'])
        ->middleware('can:inventory_adjustments.create')
        ->name('inventory-adjustments.store');
    Route::get('inventory-adjustments/{inventory_adjustment}/edit', [InventoryAdjustmentController::class, 'edit'])
        ->middleware('can:inventory_adjustments.update')
        ->name('inventory-adjustments.edit');
    Route::patch('inventory-adjustments/{inventory_adjustment}', [InventoryAdjustmentController::class, 'update'])
        ->middleware('can:inventory_adjustments.update')
        ->name('inventory-adjustments.update');
    Route::delete('inventory-adjustments/{inventory_adjustment}', [InventoryAdjustmentController::class, 'destroy'])
        ->middleware('can:inventory_adjustments.delete')
        ->name('inventory-adjustments.destroy');
    Route::get('inventory-transfers', [InventoryTransferController::class, 'index'])
        ->middleware('can:inventory_transfers.view')
        ->name('inventory-transfers.index');
    Route::get('inventory-transfers/create', [InventoryTransferController::class, 'create'])
        ->middleware('can:inventory_transfers.create')
        ->name('inventory-transfers.create');
    Route::post('inventory-transfers', [InventoryTransferController::class, 'store'])
        ->middleware('can:inventory_transfers.create')
        ->name('inventory-transfers.store');
    Route::get('inventory-transfers/{inventory_transfer}/edit', [InventoryTransferController::class, 'edit'])
        ->middleware('can:inventory_transfers.update')
        ->name('inventory-transfers.edit');
    Route::patch('inventory-transfers/{inventory_transfer}', [InventoryTransferController::class, 'update'])
        ->middleware('can:inventory_transfers.update')
        ->name('inventory-transfers.update');
    Route::delete('inventory-transfers/{inventory_transfer}', [InventoryTransferController::class, 'destroy'])
        ->middleware('can:inventory_transfers.delete')
        ->name('inventory-transfers.destroy');
});

require __DIR__.'/settings.php';
