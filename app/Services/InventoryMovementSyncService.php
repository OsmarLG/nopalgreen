<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\ProductionOrder;
use App\Models\ProductPresentation;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\RawMaterialPresentation;
use App\Models\Warehouse;
use DateTimeInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class InventoryMovementSyncService
{
    public function syncPurchase(Purchase $purchase): void
    {
        $this->deleteForReference(Purchase::class, $purchase->id);

        if ($purchase->status !== Purchase::STATUS_RECEIVED) {
            return;
        }

        $purchase->loadMissing('items');

        foreach ($purchase->items as $item) {
            InventoryMovement::query()->create([
                'warehouse_id' => $this->resolveWarehouseIdForItemType($item->item_type),
                'item_type' => $item->item_type,
                'item_id' => $item->item_id,
                'movement_type' => InventoryMovement::TYPE_PURCHASE,
                'direction' => InventoryMovement::DIRECTION_IN,
                'quantity' => $this->resolvePurchaseMovementQuantity($item),
                'unit_cost' => $item->unit_cost,
                'reference_type' => Purchase::class,
                'reference_id' => $purchase->id,
                'notes' => $purchase->notes,
                'moved_at' => $purchase->purchased_at ?? now(),
            ]);
        }
    }

    public function syncProductionOrder(ProductionOrder $productionOrder): void
    {
        $this->deleteForReference(ProductionOrder::class, $productionOrder->id);

        if ($productionOrder->status !== ProductionOrder::STATUS_COMPLETED) {
            return;
        }

        $productionOrder->loadMissing(['consumptions', 'outputs']);

        foreach ($productionOrder->consumptions as $consumption) {
            InventoryMovement::query()->create([
                'warehouse_id' => $this->resolveWarehouseIdForItemType($consumption->item_type),
                'item_type' => $consumption->item_type,
                'item_id' => $consumption->item_id,
                'movement_type' => InventoryMovement::TYPE_PRODUCTION_CONSUMPTION,
                'direction' => InventoryMovement::DIRECTION_OUT,
                'quantity' => $consumption->consumed_quantity,
                'unit_cost' => null,
                'reference_type' => ProductionOrder::class,
                'reference_id' => $productionOrder->id,
                'notes' => $productionOrder->notes,
                'moved_at' => $this->resolveProductionMovementTimestamp($productionOrder),
            ]);
        }

        foreach ($productionOrder->outputs as $output) {
            InventoryMovement::query()->create([
                'warehouse_id' => $this->resolveWarehouseIdForItemType(InventoryMovement::ITEM_TYPE_PRODUCT),
                'item_type' => InventoryMovement::ITEM_TYPE_PRODUCT,
                'item_id' => $output->product_id,
                'movement_type' => InventoryMovement::TYPE_PRODUCTION_OUTPUT,
                'direction' => InventoryMovement::DIRECTION_IN,
                'quantity' => $output->quantity,
                'unit_cost' => null,
                'reference_type' => ProductionOrder::class,
                'reference_id' => $productionOrder->id,
                'notes' => $productionOrder->notes,
                'moved_at' => $this->resolveProductionMovementTimestamp($productionOrder),
            ]);
        }
    }

    private function deleteForReference(string $referenceType, int $referenceId): void
    {
        InventoryMovement::query()
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->delete();
    }

    private function resolveWarehouseIdForItemType(string $itemType): int
    {
        $preferredWarehouseType = $itemType === InventoryMovement::ITEM_TYPE_RAW_MATERIAL
            ? Warehouse::TYPE_RAW_MATERIAL
            : Warehouse::TYPE_FINISHED_PRODUCT;

        $warehouse = Warehouse::query()
            ->where('is_active', true)
            ->whereIn('type', [$preferredWarehouseType, Warehouse::TYPE_MIXED])
            ->orderByRaw(
                'CASE WHEN type = ? THEN 0 WHEN type = ? THEN 1 ELSE 2 END',
                [$preferredWarehouseType, Warehouse::TYPE_MIXED],
            )
            ->first();

        if ($warehouse === null) {
            throw new ModelNotFoundException('No existe un almacen activo para registrar movimientos de inventario.');
        }

        return $warehouse->id;
    }

    private function resolvePurchaseMovementQuantity(PurchaseItem $purchaseItem): float
    {
        $presentationQuantity = match ($purchaseItem->presentation_type) {
            PurchaseItem::PRESENTATION_TYPE_RAW_MATERIAL => RawMaterialPresentation::query()
                ->whereKey($purchaseItem->presentation_id)
                ->value('quantity'),
            PurchaseItem::PRESENTATION_TYPE_PRODUCT => ProductPresentation::query()
                ->whereKey($purchaseItem->presentation_id)
                ->value('quantity'),
            default => null,
        };

        if ($presentationQuantity === null) {
            throw new ModelNotFoundException('La presentacion de la compra no existe.');
        }

        return round((float) $purchaseItem->quantity * (float) $presentationQuantity, 3);
    }

    private function resolveProductionMovementTimestamp(ProductionOrder $productionOrder): DateTimeInterface
    {
        return $productionOrder->finished_at
            ?? $productionOrder->started_at
            ?? $productionOrder->scheduled_for
            ?? now();
    }
}
