<?php

namespace Database\Seeders;

use App\Models\InventoryMovement;
use App\Models\ProductionOrder;
use App\Models\Purchase;
use App\Models\RawMaterial;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class InventoryMovementSeeder extends Seeder
{
    public function run(): void
    {
        $rawWarehouse = Warehouse::query()->where('type', Warehouse::TYPE_RAW_MATERIAL)->first();
        $finishedWarehouse = Warehouse::query()->where('type', Warehouse::TYPE_FINISHED_PRODUCT)->first();
        $purchase = Purchase::query()->first();
        $order = ProductionOrder::query()->first();
        $rawMaterial = RawMaterial::query()->first();

        if ($rawWarehouse && $purchase && $rawMaterial) {
            InventoryMovement::query()->firstOrCreate(
                [
                    'warehouse_id' => $rawWarehouse->id,
                    'item_type' => InventoryMovement::ITEM_TYPE_RAW_MATERIAL,
                    'item_id' => $rawMaterial->id,
                    'movement_type' => InventoryMovement::TYPE_PURCHASE,
                ],
                [
                    'direction' => InventoryMovement::DIRECTION_IN,
                    'quantity' => 10,
                    'unit_cost' => 150,
                    'reference_type' => Purchase::class,
                    'reference_id' => $purchase->id,
                    'notes' => 'Entrada inicial por compra.',
                    'moved_at' => now(),
                ],
            );
        }

        if ($finishedWarehouse && $order) {
            InventoryMovement::query()->firstOrCreate(
                [
                    'warehouse_id' => $finishedWarehouse->id,
                    'item_type' => InventoryMovement::ITEM_TYPE_PRODUCT,
                    'item_id' => $order->product_id,
                    'movement_type' => InventoryMovement::TYPE_PRODUCTION_OUTPUT,
                ],
                [
                    'direction' => InventoryMovement::DIRECTION_IN,
                    'quantity' => $order->planned_quantity,
                    'unit_cost' => null,
                    'reference_type' => ProductionOrder::class,
                    'reference_id' => $order->id,
                    'notes' => 'Salida de ejemplo por produccion.',
                    'moved_at' => now(),
                ],
            );
        }
    }
}
