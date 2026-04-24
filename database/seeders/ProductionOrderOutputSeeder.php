<?php

namespace Database\Seeders;

use App\Models\ProductionOrder;
use App\Models\ProductionOrderOutput;
use Illuminate\Database\Seeder;

class ProductionOrderOutputSeeder extends Seeder
{
    public function run(): void
    {
        $order = ProductionOrder::query()->first();

        if (! $order) {
            return;
        }

        ProductionOrderOutput::query()->firstOrCreate(
            [
                'production_order_id' => $order->id,
                'product_id' => $order->product_id,
            ],
            [
                'quantity' => $order->planned_quantity,
                'unit_id' => $order->unit_id,
                'is_main_output' => true,
            ],
        );
    }
}
