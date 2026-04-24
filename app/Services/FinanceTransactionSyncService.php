<?php

namespace App\Services;

use App\Models\FinanceTransaction;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\RawMaterial;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Model;

class FinanceTransactionSyncService
{
    public function syncPurchase(Purchase $purchase): void
    {
        $this->deleteForReference(Purchase::class, $purchase->id);

        if ($purchase->status !== Purchase::STATUS_RECEIVED) {
            return;
        }

        $purchase->loadMissing('supplier:id,name', 'items');
        $amount = round((float) $purchase->items->sum('total'), 2);

        if ($amount <= 0) {
            return;
        }

        $this->createAutomaticEntry(
            $purchase,
            FinanceTransaction::TYPE_EXPENSE,
            FinanceTransaction::SOURCE_PURCHASE,
            "Compra {$purchase->folio}",
            "Proveedor: {$purchase->supplier->name}",
            $amount,
            $purchase->purchased_at ?? now(),
            $purchase->notes,
        );
    }

    public function syncSale(Sale $sale): void
    {
        $this->deleteForReference(Sale::class, $sale->id);

        if ($sale->status !== Sale::STATUS_COMPLETED) {
            return;
        }

        $sale->loadMissing('customer:id,name');
        $amount = round((float) $sale->total, 2);

        if ($amount <= 0) {
            return;
        }

        $detail = $sale->customer?->name
            ? "Cliente: {$sale->customer->name}"
            : 'Venta sin cliente registrado';

        $this->createAutomaticEntry(
            $sale,
            FinanceTransaction::TYPE_INCOME,
            FinanceTransaction::SOURCE_SALE,
            "Venta {$sale->folio}",
            $detail,
            $amount,
            $sale->completed_at ?? $sale->sale_date ?? now(),
            $sale->notes,
        );
    }

    public function syncProductionOrder(ProductionOrder $productionOrder): void
    {
        $this->deleteForReference(ProductionOrder::class, $productionOrder->id);

        if ($productionOrder->status !== ProductionOrder::STATUS_COMPLETED) {
            return;
        }

        $productionOrder->loadMissing(['product:id,name', 'consumptions']);

        $amount = round($productionOrder->consumptions->sum(function ($consumption): float {
            return (float) $consumption->consumed_quantity * $this->resolveAverageUnitCost($consumption->item_type, (int) $consumption->item_id);
        }), 2);

        if ($amount <= 0) {
            return;
        }

        $this->createAutomaticEntry(
            $productionOrder,
            FinanceTransaction::TYPE_EXPENSE,
            FinanceTransaction::SOURCE_PRODUCTION,
            "Costo de produccion {$productionOrder->folio}",
            "Producto: {$productionOrder->product->name}",
            $amount,
            $productionOrder->finished_at ?? $productionOrder->started_at ?? $productionOrder->scheduled_for ?? now(),
            $productionOrder->notes,
        );
    }

    public function syncInventoryAdjustment(InventoryMovement $movement): void
    {
        $this->deleteForReference(InventoryMovement::class, $movement->id);

        if ($movement->movement_type !== InventoryMovement::TYPE_WASTE) {
            return;
        }

        $amount = round((float) $movement->quantity * $this->resolveMovementUnitCost($movement), 2);

        if ($amount <= 0) {
            return;
        }

        $movement->loadMissing(['rawMaterial:id,name', 'product:id,name', 'warehouse:id,name']);

        $itemName = $movement->item_type === InventoryMovement::ITEM_TYPE_RAW_MATERIAL
            ? $movement->rawMaterial?->name
            : $movement->product?->name;

        $detail = collect([
            $itemName ? "Item: {$itemName}" : null,
            $movement->warehouse?->name ? "Almacen: {$movement->warehouse->name}" : null,
        ])->filter()->implode(' - ');

        $this->createAutomaticEntry(
            $movement,
            FinanceTransaction::TYPE_LOSS,
            FinanceTransaction::SOURCE_WASTE,
            'Merma de inventario',
            $detail,
            $amount,
            $movement->moved_at,
            $movement->notes,
        );
    }

    public function deleteInventoryAdjustment(InventoryMovement $movement): void
    {
        $this->deleteForReference(InventoryMovement::class, $movement->id);
    }

    private function createAutomaticEntry(
        Model $reference,
        string $type,
        string $source,
        string $concept,
        ?string $detail,
        float $amount,
        \DateTimeInterface $occurredAt,
        ?string $notes,
    ): void {
        FinanceTransaction::query()->create([
            'folio' => $this->nextAutomaticFolio(),
            'transaction_type' => $type,
            'direction' => in_array($type, [FinanceTransaction::TYPE_INCOME, FinanceTransaction::TYPE_COLLECTION], true)
                ? FinanceTransaction::DIRECTION_IN
                : FinanceTransaction::DIRECTION_OUT,
            'source' => $source,
            'concept' => $concept,
            'detail' => $detail,
            'amount' => $amount,
            'status' => FinanceTransaction::STATUS_POSTED,
            'is_manual' => false,
            'affects_balance' => true,
            'created_by' => null,
            'reference_type' => $reference::class,
            'reference_id' => $reference->getKey(),
            'occurred_at' => $occurredAt,
            'notes' => $notes,
            'meta' => null,
        ]);
    }

    private function deleteForReference(string $referenceType, int $referenceId): void
    {
        FinanceTransaction::query()
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->delete();
    }

    private function resolveMovementUnitCost(InventoryMovement $movement): float
    {
        return $movement->unit_cost !== null
            ? (float) $movement->unit_cost
            : $this->resolveAverageUnitCost($movement->item_type, (int) $movement->item_id);
    }

    private function resolveAverageUnitCost(string $itemType, int $itemId): float
    {
        $aggregate = InventoryMovement::query()
            ->where('item_type', $itemType)
            ->where('item_id', $itemId)
            ->whereNotNull('unit_cost')
            ->where('direction', InventoryMovement::DIRECTION_IN)
            ->selectRaw('COALESCE(SUM(quantity * unit_cost), 0) as total_cost')
            ->selectRaw('COALESCE(SUM(quantity), 0) as total_quantity')
            ->first();

        $totalQuantity = (float) ($aggregate?->total_quantity ?? 0);

        if ($totalQuantity <= 0) {
            return $this->resolveFallbackUnitCost($itemType, $itemId);
        }

        return round((float) $aggregate->total_cost / $totalQuantity, 2);
    }

    private function resolveFallbackUnitCost(string $itemType, int $itemId): float
    {
        $latestPurchaseCost = PurchaseItem::query()
            ->where('item_type', $itemType)
            ->where('item_id', $itemId)
            ->latest('id')
            ->value('unit_cost');

        if ($latestPurchaseCost !== null) {
            return round((float) $latestPurchaseCost, 2);
        }

        if ($itemType === InventoryMovement::ITEM_TYPE_PRODUCT) {
            $salePrice = Product::query()->whereKey($itemId)->value('sale_price');

            if ($salePrice !== null) {
                return round((float) $salePrice, 2);
            }
        }

        if ($itemType === InventoryMovement::ITEM_TYPE_RAW_MATERIAL) {
            $baseUnitId = RawMaterial::query()->whereKey($itemId)->value('base_unit_id');

            if ($baseUnitId !== null) {
                return 0.0;
            }
        }

        return 0.0;
    }

    private function nextAutomaticFolio(): string
    {
        $sequence = FinanceTransaction::query()->count() + 1;

        do {
            $folio = 'FIN-'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
            $sequence++;
        } while (FinanceTransaction::query()->where('folio', $folio)->exists());

        return $folio;
    }
}
