<?php

namespace Database\Seeders;

use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class PurchaseSeeder extends Seeder
{
    public function run(): void
    {
        $supplier = Supplier::query()->first();

        if (! $supplier) {
            return;
        }

        Purchase::query()->firstOrCreate(
            ['folio' => 'COM-000001'],
            [
                'supplier_id' => $supplier->id,
                'status' => Purchase::STATUS_RECEIVED,
                'purchased_at' => now(),
                'notes' => 'Compra inicial de ejemplo.',
            ],
        );
    }
}
