<?php

use App\Models\RawMaterial;
use App\Models\Unit;
use App\Models\User;
use App\Support\Access;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('database seeder only seeds base access users units and raw materials', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Role::query()->pluck('name')->all())
        ->toContain(...Access::ROLES);

    expect(Permission::query()->pluck('name')->all())
        ->toContain(...Access::PERMISSIONS);

    $master = User::query()->where('username', 'osmarlg')->firstOrFail();
    $admin = User::query()->where('username', 'admin')->firstOrFail();
    $deliveryEmployee = User::query()->where('username', 'repartidor')->firstOrFail();
    $plantEmployee = User::query()->where('username', 'planta')->firstOrFail();

    expect($master->getRoleNames()->all())->toBe(['master']);
    expect($admin->getRoleNames()->all())->toBe(['admin']);
    expect($deliveryEmployee->getRoleNames()->all())->toBe(['empleado', 'repartidor']);
    expect($plantEmployee->getRoleNames()->all())->toBe(['empleado', 'planta']);
    expect($deliveryEmployee->attendance_starts_at)->not->toBeNull();
    expect($plantEmployee->attendance_starts_at)->not->toBeNull();

    expect(Unit::query()->pluck('code')->all())
        ->toContain('kg', 'g', 'l', 'ml', 'pz', 'paq');

    expect(RawMaterial::query()->pluck('slug')->all())
        ->toContain(
            'maiz-blanco',
            'maiz-amarillo',
            'harina',
            'aceite',
            'sal',
            'bolsa-transparente',
        );

    expect(DB::table('suppliers')->count())->toBe(3);
    expect(DB::table('products')->count())->toBe(3);
    expect(DB::table('raw_material_presentations')->count())->toBe(6);
    expect(DB::table('product_presentations')->count())->toBe(3);
    expect(DB::table('raw_material_suppliers')->count())->toBe(6);
    expect(DB::table('product_suppliers')->count())->toBe(3);
    expect(DB::table('recipes')->count())->toBe(3);
    expect(DB::table('recipe_items')->count())->toBe(4);

    expect(DB::table('purchases')->count())->toBe(0);
    expect(DB::table('purchase_items')->count())->toBe(0);
    expect(DB::table('production_orders')->count())->toBe(0);
    expect(DB::table('production_order_consumptions')->count())->toBe(0);
    expect(DB::table('production_order_outputs')->count())->toBe(0);
    expect(DB::table('sales')->count())->toBe(0);
    expect(DB::table('sale_items')->count())->toBe(0);
    expect(DB::table('inventory_movements')->count())->toBe(0);
    expect(DB::table('finance_transactions')->count())->toBe(0);
});
