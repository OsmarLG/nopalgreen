<?php

namespace App\Http\Middleware;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        /** @var User|null $user */
        $user = $request->user();
        $branding = AppSetting::current();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'branding' => [
                'app_name' => $branding->app_name,
                'app_tagline' => $branding->app_tagline,
                'logo_url' => $branding->logo_url,
                'favicon_url' => $branding->favicon_url,
            ],
            'status' => fn () => $request->session()->get('status'),
            'auth' => [
                'user' => $user?->loadMissing('roles:id,name', 'permissions:id,name'),
                'can' => [
                    'viewUsers' => $user?->can('users.view') ?? false,
                    'createUsers' => $user?->can('users.create') ?? false,
                    'updateUsers' => $user?->can('users.update') ?? false,
                    'deleteUsers' => $user?->can('users.delete') ?? false,
                    'viewRoles' => $user?->can('roles.view') ?? false,
                    'createRoles' => $user?->can('roles.create') ?? false,
                    'updateRoles' => $user?->can('roles.update') ?? false,
                    'viewPermissions' => $user?->can('permissions.view') ?? false,
                    'updateBranding' => $user?->can('branding.update') ?? false,
                    'viewUnits' => $user?->can('units.view') ?? false,
                    'createUnits' => $user?->can('units.create') ?? false,
                    'updateUnits' => $user?->can('units.update') ?? false,
                    'deleteUnits' => $user?->can('units.delete') ?? false,
                    'viewSuppliers' => $user?->can('suppliers.view') ?? false,
                    'createSuppliers' => $user?->can('suppliers.create') ?? false,
                    'updateSuppliers' => $user?->can('suppliers.update') ?? false,
                    'deleteSuppliers' => $user?->can('suppliers.delete') ?? false,
                    'viewCustomers' => $user?->can('customers.view') ?? false,
                    'createCustomers' => $user?->can('customers.create') ?? false,
                    'updateCustomers' => $user?->can('customers.update') ?? false,
                    'deleteCustomers' => $user?->can('customers.delete') ?? false,
                    'viewSales' => $user?->can('sales.view') ?? false,
                    'createSales' => $user?->can('sales.create') ?? false,
                    'updateSales' => $user?->can('sales.update') ?? false,
                    'deleteSales' => $user?->can('sales.delete') ?? false,
                    'viewRawMaterials' => $user?->can('raw_materials.view') ?? false,
                    'createRawMaterials' => $user?->can('raw_materials.create') ?? false,
                    'updateRawMaterials' => $user?->can('raw_materials.update') ?? false,
                    'deleteRawMaterials' => $user?->can('raw_materials.delete') ?? false,
                    'viewProducts' => $user?->can('products.view') ?? false,
                    'createProducts' => $user?->can('products.create') ?? false,
                    'updateProducts' => $user?->can('products.update') ?? false,
                    'deleteProducts' => $user?->can('products.delete') ?? false,
                    'viewPresentations' => $user?->can('presentations.view') ?? false,
                    'createPresentations' => $user?->can('presentations.create') ?? false,
                    'updatePresentations' => $user?->can('presentations.update') ?? false,
                    'deletePresentations' => $user?->can('presentations.delete') ?? false,
                    'viewRecipes' => $user?->can('recipes.view') ?? false,
                    'createRecipes' => $user?->can('recipes.create') ?? false,
                    'updateRecipes' => $user?->can('recipes.update') ?? false,
                    'deleteRecipes' => $user?->can('recipes.delete') ?? false,
                    'viewProductionOrders' => $user?->can('production_orders.view') ?? false,
                    'createProductionOrders' => $user?->can('production_orders.create') ?? false,
                    'updateProductionOrders' => $user?->can('production_orders.update') ?? false,
                    'deleteProductionOrders' => $user?->can('production_orders.delete') ?? false,
                    'viewPurchases' => $user?->can('purchases.view') ?? false,
                    'createPurchases' => $user?->can('purchases.create') ?? false,
                    'updatePurchases' => $user?->can('purchases.update') ?? false,
                    'deletePurchases' => $user?->can('purchases.delete') ?? false,
                    'viewInventoryAdjustments' => $user?->can('inventory_adjustments.view') ?? false,
                    'createInventoryAdjustments' => $user?->can('inventory_adjustments.create') ?? false,
                    'updateInventoryAdjustments' => $user?->can('inventory_adjustments.update') ?? false,
                    'deleteInventoryAdjustments' => $user?->can('inventory_adjustments.delete') ?? false,
                    'viewInventoryTransfers' => $user?->can('inventory_transfers.view') ?? false,
                    'createInventoryTransfers' => $user?->can('inventory_transfers.create') ?? false,
                    'updateInventoryTransfers' => $user?->can('inventory_transfers.update') ?? false,
                    'deleteInventoryTransfers' => $user?->can('inventory_transfers.delete') ?? false,
                    'viewInventoryMovements' => $user?->can('inventory_movements.view') ?? false,
                    'viewReports' => $user?->can('reports.view') ?? false,
                    'viewFinances' => $user?->can('finances.view') ?? false,
                    'createFinances' => $user?->can('finances.create') ?? false,
                    'updateFinances' => $user?->can('finances.update') ?? false,
                    'deleteFinances' => $user?->can('finances.delete') ?? false,
                    'viewEmployees' => $user?->can('employees.view') ?? false,
                    'markAttendance' => $user?->can('attendance.mark') ?? false,
                    'manageAttendance' => $user?->can('attendance.manage') ?? false,
                ],
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
