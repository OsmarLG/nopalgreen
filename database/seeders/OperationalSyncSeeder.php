<?php

namespace Database\Seeders;

use App\Models\InventoryMovement;
use App\Models\ProductionOrder;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\RawMaterial;
use App\Models\RawMaterialPresentation;
use App\Models\Recipe;
use App\Models\Supplier;
use App\Models\Unit;
use App\Services\FinanceTransactionSyncService;
use App\Services\InventoryMovementSyncService;
use App\Services\ProductionOrderService;
use App\Services\PurchaseService;
use Illuminate\Database\Seeder;

class OperationalSyncSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedOperationalExamples();

        $inventoryMovementSyncService = app(InventoryMovementSyncService::class);
        $financeTransactionSyncService = app(FinanceTransactionSyncService::class);

        Purchase::query()
            ->with('items')
            ->get()
            ->each(function (Purchase $purchase) use ($inventoryMovementSyncService, $financeTransactionSyncService): void {
                $inventoryMovementSyncService->syncPurchase($purchase);
                $financeTransactionSyncService->syncPurchase($purchase);
            });

        ProductionOrder::query()
            ->with(['consumptions', 'outputs'])
            ->get()
            ->each(function (ProductionOrder $productionOrder) use ($inventoryMovementSyncService, $financeTransactionSyncService): void {
                $inventoryMovementSyncService->syncProductionOrder($productionOrder);
                $financeTransactionSyncService->syncProductionOrder($productionOrder);
            });

        InventoryMovement::query()
            ->where('movement_type', InventoryMovement::TYPE_WASTE)
            ->get()
            ->each(function (InventoryMovement $movement) use ($financeTransactionSyncService): void {
                $financeTransactionSyncService->syncInventoryAdjustment($movement);
            });
    }

    private function seedOperationalExamples(): void
    {
        $supplier = Supplier::query()->first();
        $rawMaterial = RawMaterial::query()->where('slug', 'maiz-blanco')->first();
        $presentation = RawMaterialPresentation::query()->where('raw_material_id', $rawMaterial?->id)->first();
        $recipe = Recipe::query()
            ->whereHas('product', fn ($query) => $query->where('slug', 'tortilla-blanca'))
            ->first();
        $unit = Unit::query()->where('code', 'kg')->first();

        if ($supplier && $rawMaterial && $presentation && ! Purchase::query()->where('notes', 'Compra inicial de ejemplo.')->exists()) {
            app(PurchaseService::class)->create([
                'supplier_id' => $supplier->id,
                'status' => Purchase::STATUS_RECEIVED,
                'purchased_at' => now(),
                'notes' => 'Compra inicial de ejemplo.',
                'items' => [
                    [
                        'item_type' => PurchaseItem::ITEM_TYPE_RAW_MATERIAL,
                        'item_id' => $rawMaterial->id,
                        'presentation_type' => PurchaseItem::PRESENTATION_TYPE_RAW_MATERIAL,
                        'presentation_id' => $presentation->id,
                        'quantity' => 10,
                        'unit_cost' => 150,
                        'total' => 1500,
                    ],
                ],
            ]);
        }

        if (! $recipe || ! $rawMaterial || ! $unit || ProductionOrder::query()->where('notes', 'Orden inicial de ejemplo.')->exists()) {
            return;
        }

        app(ProductionOrderService::class)->create([
            'product_id' => $recipe->product_id,
            'recipe_id' => $recipe->id,
            'planned_quantity' => 50,
            'produced_quantity' => 50,
            'unit_id' => $unit->id,
            'status' => ProductionOrder::STATUS_COMPLETED,
            'scheduled_for' => now()->subHours(4),
            'started_at' => now()->subHours(3),
            'finished_at' => now()->subHours(2),
            'notes' => 'Orden inicial de ejemplo.',
            'consumptions' => [
                [
                    'item_type' => InventoryMovement::ITEM_TYPE_RAW_MATERIAL,
                    'item_id' => $rawMaterial->id,
                    'planned_quantity' => 1,
                    'consumed_quantity' => 1,
                    'unit_id' => $unit->id,
                ],
            ],
        ]);
    }
}
