<?php

namespace App\Support;

use App\Models\User;

class Access
{
    /**
     * Available roles in the application.
     *
     * @var list<string>
     */
    public const ROLES = [
        'master',
        'admin',
        'empleado',
        'planta',
        'repartidor',
    ];

    /**
     * Available permissions in the application.
     *
     * @var list<string>
     */
    public const PERMISSIONS = [
        'users.view',
        'users.create',
        'users.update',
        'users.delete',
        'roles.view',
        'roles.create',
        'roles.update',
        'permissions.view',
        'branding.update',
        'units.view',
        'units.create',
        'units.update',
        'units.delete',
        'suppliers.view',
        'suppliers.create',
        'suppliers.update',
        'suppliers.delete',
        'customers.view',
        'customers.create',
        'customers.update',
        'customers.delete',
        'sales.view',
        'sales.create',
        'sales.update',
        'sales.delete',
        'raw_materials.view',
        'raw_materials.create',
        'raw_materials.update',
        'raw_materials.delete',
        'products.view',
        'products.create',
        'products.update',
        'products.delete',
        'presentations.view',
        'presentations.create',
        'presentations.update',
        'presentations.delete',
        'recipes.view',
        'recipes.create',
        'recipes.update',
        'recipes.delete',
        'production_orders.view',
        'production_orders.create',
        'production_orders.update',
        'production_orders.delete',
        'purchases.view',
        'purchases.create',
        'purchases.update',
        'purchases.delete',
        'inventory_adjustments.view',
        'inventory_adjustments.create',
        'inventory_adjustments.update',
        'inventory_adjustments.delete',
        'inventory_transfers.view',
        'inventory_transfers.create',
        'inventory_transfers.update',
        'inventory_transfers.delete',
        'inventory_movements.view',
        'reports.view',
        'finances.view',
        'finances.create',
        'finances.update',
        'finances.delete',
        'employees.view',
        'attendance.mark',
        'attendance.manage',
    ];

    public const ADMINISTRATION_ROLES = [
        'master',
        'admin',
    ];

    public const PROTECTED_ROLES = [
        'master',
    ];

    /**
     * Role-to-permission mapping.
     *
     * @return array<string, list<string>>
     */
    public static function rolePermissions(): array
    {
        return [
            'master' => self::PERMISSIONS,
            'admin' => [
                'users.view',
                'users.create',
                'users.update',
                'users.delete',
                'roles.view',
                'roles.create',
                'roles.update',
                'permissions.view',
                'branding.update',
                'units.view',
                'units.create',
                'units.update',
                'units.delete',
                'suppliers.view',
                'suppliers.create',
                'suppliers.update',
                'suppliers.delete',
                'customers.view',
                'customers.create',
                'customers.update',
                'customers.delete',
                'sales.view',
                'sales.create',
                'sales.update',
                'sales.delete',
                'raw_materials.view',
                'raw_materials.create',
                'raw_materials.update',
                'raw_materials.delete',
                'products.view',
                'products.create',
                'products.update',
                'products.delete',
                'presentations.view',
                'presentations.create',
                'presentations.update',
                'presentations.delete',
                'recipes.view',
                'recipes.create',
                'recipes.update',
                'recipes.delete',
                'production_orders.view',
                'production_orders.create',
                'production_orders.update',
                'production_orders.delete',
                'purchases.view',
                'purchases.create',
                'purchases.update',
                'purchases.delete',
                'inventory_adjustments.view',
                'inventory_adjustments.create',
                'inventory_adjustments.update',
                'inventory_adjustments.delete',
                'inventory_transfers.view',
                'inventory_transfers.create',
                'inventory_transfers.update',
                'inventory_transfers.delete',
                'inventory_movements.view',
                'reports.view',
                'finances.view',
                'finances.create',
                'finances.update',
                'finances.delete',
                'employees.view',
                'attendance.mark',
                'attendance.manage',
            ],
            'empleado' => [
                'attendance.mark',
            ],
            'planta' => [
                'products.view',
                'raw_materials.view',
                'presentations.view',
                'recipes.view',
                'production_orders.view',
                'production_orders.create',
                'production_orders.update',
                'inventory_movements.view',
            ],
            'repartidor' => [
                'customers.view',
                'sales.view',
                'sales.update',
            ],
        ];
    }

    public static function isProtectedRoleName(string $roleName): bool
    {
        return in_array($roleName, self::PROTECTED_ROLES, true);
    }

    public static function userHasProtectedRole(User $user): bool
    {
        return $user->roles->pluck('name')->contains(
            fn (string $roleName): bool => self::isProtectedRoleName($roleName),
        );
    }

    public static function canManageProtectedRecords(User $actor): bool
    {
        return $actor->hasRole('master');
    }
}
