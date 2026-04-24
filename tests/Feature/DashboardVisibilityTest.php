<?php

use App\Models\AttendanceRecord;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\Recipe;
use App\Models\Sale;
use App\Models\Unit;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Database\Seeders\RolesAndMasterUserSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('repartidor dashboard shows own delivery metrics only', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $repartidor = User::factory()->create(['username' => 'repartidor-dashboard']);
    $repartidor->assignRole('repartidor');

    $otherRepartidor = User::factory()->create(['username' => 'repartidor-otro']);
    $otherRepartidor->assignRole('repartidor');

    Sale::factory()->create([
        'delivery_user_id' => $repartidor->id,
        'sale_type' => Sale::TYPE_DELIVERY,
        'status' => Sale::STATUS_ASSIGNED,
    ]);
    Sale::factory()->create([
        'delivery_user_id' => $repartidor->id,
        'sale_type' => Sale::TYPE_DELIVERY,
        'status' => Sale::STATUS_COMPLETED,
    ]);
    Sale::factory()->create([
        'delivery_user_id' => $otherRepartidor->id,
        'sale_type' => Sale::TYPE_DELIVERY,
        'status' => Sale::STATUS_ASSIGNED,
    ]);

    $response = $this->actingAs($repartidor)->get(route('dashboard'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->where('roleScope', 'repartidor')
        ->where('cards.0.value', '1')
        ->where('cards.1.value', '1')
        ->has('lists.0.items', 2)
        ->where('auth.can.viewUsers', false)
        ->where('auth.can.viewSales', true)
    );
});

test('planta dashboard exposes production permissions and production metrics', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $planta = User::factory()->create(['username' => 'planta-dashboard']);
    $planta->assignRole('planta');

    $unit = Unit::factory()->create();
    $product = Product::factory()->create(['base_unit_id' => $unit->id]);
    $recipe = Recipe::factory()->create([
        'product_id' => $product->id,
        'yield_unit_id' => $unit->id,
    ]);

    ProductionOrder::factory()->create([
        'product_id' => $product->id,
        'recipe_id' => $recipe->id,
        'unit_id' => $unit->id,
        'status' => ProductionOrder::STATUS_PLANNED,
    ]);

    $response = $this->actingAs($planta)->get(route('dashboard'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->where('roleScope', 'planta')
        ->where('cards.0.value', '1')
        ->where('auth.can.viewProductionOrders', true)
        ->where('auth.can.createProductionOrders', true)
        ->where('auth.can.viewUsers', false)
        ->where('auth.can.viewSales', false)
    );
});

test('empleado dashboard shows attendance summary only', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $employee = User::factory()->create(['username' => 'empleado-dashboard']);
    $employee->assignRole('empleado');
    $employee->forceFill(['attendance_starts_at' => '2026-04-01'])->save();

    Carbon::setTestNow(CarbonImmutable::parse('2026-04-23 08:05:00'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-23 08:05:00'));

    $response = $this->actingAs($employee)->get(route('dashboard'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->where('roleScope', 'empleado')
        ->where('cards.0.value', 'Pendiente')
        ->where('auth.can.markAttendance', true)
        ->where('auth.can.viewUsers', false)
        ->has('lists.0.items', 2)
    );

    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

test('admin dashboard includes attendance overview metrics', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create(['username' => 'admin-dashboard-attendance']);
    $admin->assignRole('admin');

    $employeeOnTime = User::factory()->create(['username' => 'empleado-ontime']);
    $employeeOnTime->assignRole('empleado');

    $employeeTardy = User::factory()->create(['username' => 'empleado-tardy']);
    $employeeTardy->assignRole('empleado');

    AttendanceRecord::factory()->create([
        'user_id' => $employeeOnTime->id,
        'attendance_date' => '2026-04-23',
        'expected_check_in_at' => '2026-04-23 08:00:00',
        'expected_check_out_at' => '2026-04-23 17:00:00',
        'absence_after_at' => '2026-04-23 09:00:00',
        'check_in_at' => '2026-04-23 08:05:00',
        'check_in_status' => AttendanceRecord::STATUS_ON_TIME,
    ]);

    AttendanceRecord::factory()->create([
        'user_id' => $employeeTardy->id,
        'attendance_date' => '2026-04-23',
        'expected_check_in_at' => '2026-04-23 08:00:00',
        'expected_check_out_at' => '2026-04-23 17:00:00',
        'absence_after_at' => '2026-04-23 09:00:00',
        'check_in_at' => '2026-04-23 08:25:00',
        'check_in_status' => AttendanceRecord::STATUS_TARDY,
        'late_minutes' => 25,
    ]);

    Carbon::setTestNow(CarbonImmutable::parse('2026-04-23 08:30:00'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-23 08:30:00'));

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->where('roleScope', 'admin')
        ->where('lists.0.title', 'Asistencia de hoy')
        ->has('lists.0.items', 2)
    );

    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

test('repartidor only sees his assigned or completed sales in sales index', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $repartidor = User::factory()->create(['username' => 'repartidor-sales']);
    $repartidor->assignRole('repartidor');

    $otherRepartidor = User::factory()->create(['username' => 'repartidor-sales-other']);
    $otherRepartidor->assignRole('repartidor');

    $ownSale = Sale::factory()->create([
        'folio' => 'VTA-OWN-001',
        'delivery_user_id' => $repartidor->id,
        'sale_type' => Sale::TYPE_DELIVERY,
        'status' => Sale::STATUS_ASSIGNED,
    ]);

    Sale::factory()->create([
        'folio' => 'VTA-OTHER-001',
        'delivery_user_id' => $otherRepartidor->id,
        'sale_type' => Sale::TYPE_DELIVERY,
        'status' => Sale::STATUS_ASSIGNED,
    ]);

    Sale::factory()->create([
        'folio' => 'VTA-DIRECT-001',
        'sale_type' => Sale::TYPE_DIRECT,
        'status' => Sale::STATUS_COMPLETED,
    ]);

    $response = $this->actingAs($repartidor)->get(route('sales.index'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('sales/index')
        ->has('sales.data', 1)
        ->where('sales.data.0.id', $ownSale->id)
        ->where('sales.data.0.folio', 'VTA-OWN-001')
    );
});
