<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Warehouse;
use DateTimeInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SaleInventorySyncService
{
    public function sync(Sale $sale): void
    {
        $this->deleteForReference(Sale::class, $sale->id);

        if ($sale->status === Sale::STATUS_CANCELLED || $sale->status === Sale::STATUS_DRAFT) {
            return;
        }

        $sale->loadMissing('items');

        if ($sale->sale_type === Sale::TYPE_DIRECT && $sale->status === Sale::STATUS_COMPLETED) {
            foreach ($sale->items as $item) {
                $this->createMovement($sale, $item, InventoryMovement::TYPE_SALE, InventoryMovement::DIRECTION_OUT, (float) $item->sold_quantity);
            }

            return;
        }

        if ($sale->sale_type === Sale::TYPE_DELIVERY && in_array($sale->status, [Sale::STATUS_ASSIGNED, Sale::STATUS_COMPLETED], true)) {
            foreach ($sale->items as $item) {
                $this->createMovement($sale, $item, InventoryMovement::TYPE_SALE_DISPATCH, InventoryMovement::DIRECTION_OUT, (float) $item->quantity);

                if ($sale->status === Sale::STATUS_COMPLETED && (float) $item->returned_quantity > 0) {
                    $this->createMovement($sale, $item, InventoryMovement::TYPE_RETURN, InventoryMovement::DIRECTION_IN, (float) $item->returned_quantity);
                }
            }
        }
    }

    private function createMovement(Sale $sale, SaleItem $item, string $movementType, string $direction, float $quantity): void
    {
        if ($quantity <= 0) {
            return;
        }

        InventoryMovement::query()->create([
            'warehouse_id' => $this->resolveFinishedProductWarehouseId(),
            'item_type' => InventoryMovement::ITEM_TYPE_PRODUCT,
            'item_id' => $item->product_id,
            'movement_type' => $movementType,
            'direction' => $direction,
            'quantity' => round($quantity, 3),
            'unit_cost' => $item->unit_price,
            'reference_type' => Sale::class,
            'reference_id' => $sale->id,
            'notes' => $sale->notes,
            'moved_at' => $this->resolveMovedAt($sale),
        ]);
    }

    private function deleteForReference(string $referenceType, int $referenceId): void
    {
        InventoryMovement::query()
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->delete();
    }

    private function resolveFinishedProductWarehouseId(): int
    {
        $warehouse = Warehouse::query()
            ->where('is_active', true)
            ->whereIn('type', [Warehouse::TYPE_FINISHED_PRODUCT, Warehouse::TYPE_MIXED])
            ->orderByRaw(
                'CASE WHEN type = ? THEN 0 WHEN type = ? THEN 1 ELSE 2 END',
                [Warehouse::TYPE_FINISHED_PRODUCT, Warehouse::TYPE_MIXED],
            )
            ->first();

        if ($warehouse === null) {
            throw new ModelNotFoundException('No existe un almacen activo de producto terminado para registrar ventas.');
        }

        return $warehouse->id;
    }

    private function resolveMovedAt(Sale $sale): DateTimeInterface
    {
        return $sale->completed_at
            ?? $sale->delivery_date
            ?? $sale->sale_date
            ?? now();
    }
}
