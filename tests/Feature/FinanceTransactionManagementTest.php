<?php

use App\Models\FinanceTransaction;
use App\Models\User;
use Database\Seeders\RolesAndMasterUserSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('admin can view the finances page', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    FinanceTransaction::factory()->create([
        'folio' => 'FIN-000123',
        'concept' => 'Ingreso de caja',
    ]);

    $admin = User::factory()->create(['username' => 'financeadmin01']);
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get(route('finances.index'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('finances/index')
        ->has('transactions.data', 1)
        ->where('transactions.data.0.folio', 'FIN-000123')
    );
});

test('admin can create and update a manual finance transaction', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create(['username' => 'financeadmin02']);
    $admin->assignRole('admin');

    $createResponse = $this->actingAs($admin)->post(route('finances.store'), [
        'transaction_type' => FinanceTransaction::TYPE_EXPENSE,
        'concept' => 'Pago de servicio',
        'detail' => 'Gas de la planta',
        'amount' => 850.75,
        'status' => FinanceTransaction::STATUS_POSTED,
        'occurred_at' => now()->format('Y-m-d H:i:s'),
        'notes' => 'Pagado en efectivo',
    ]);

    $transaction = FinanceTransaction::query()->firstOrFail();

    $createResponse->assertRedirect(route('finances.edit', $transaction));
    $this->assertDatabaseHas('finance_transactions', [
        'id' => $transaction->id,
        'transaction_type' => FinanceTransaction::TYPE_EXPENSE,
        'direction' => FinanceTransaction::DIRECTION_OUT,
        'source' => FinanceTransaction::SOURCE_MANUAL,
        'concept' => 'Pago de servicio',
        'amount' => '850.75',
        'is_manual' => true,
        'created_by' => $admin->id,
    ]);

    $updateResponse = $this->actingAs($admin)->patch(route('finances.update', $transaction), [
        'transaction_type' => FinanceTransaction::TYPE_DEBT,
        'concept' => 'Cuenta por cobrar',
        'detail' => 'Cliente pendiente',
        'amount' => 1200,
        'status' => FinanceTransaction::STATUS_PENDING,
        'occurred_at' => now()->addHour()->format('Y-m-d H:i:s'),
        'notes' => 'Se liquida manana',
    ]);

    $updateResponse->assertRedirect(route('finances.edit', $transaction));
    $this->assertDatabaseHas('finance_transactions', [
        'id' => $transaction->id,
        'transaction_type' => FinanceTransaction::TYPE_DEBT,
        'direction' => FinanceTransaction::DIRECTION_IN,
        'status' => FinanceTransaction::STATUS_PENDING,
        'concept' => 'Cuenta por cobrar',
        'amount' => '1200.00',
        'affects_balance' => false,
    ]);
});

test('admin can delete manual finance transactions only', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create(['username' => 'financeadmin03']);
    $admin->assignRole('admin');

    $manualTransaction = FinanceTransaction::factory()->create([
        'is_manual' => true,
        'source' => FinanceTransaction::SOURCE_MANUAL,
    ]);

    $automaticTransaction = FinanceTransaction::factory()->create([
        'is_manual' => false,
        'source' => FinanceTransaction::SOURCE_PURCHASE,
    ]);

    $deleteResponse = $this->actingAs($admin)->delete(route('finances.destroy', $manualTransaction));
    $deleteResponse->assertRedirect(route('finances.index'));
    $this->assertDatabaseMissing('finance_transactions', ['id' => $manualTransaction->id]);

    $blockedResponse = $this->actingAs($admin)->delete(route('finances.destroy', $automaticTransaction));
    $blockedResponse->assertNotFound();
    $this->assertDatabaseHas('finance_transactions', ['id' => $automaticTransaction->id]);
});
