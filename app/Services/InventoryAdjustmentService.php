<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\RawMaterial;
use App\Models\Warehouse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class InventoryAdjustmentService
{
    public function __construct(private FinanceTransactionSyncService $financeTransactionSyncService) {}

    /**
     * @return LengthAwarePaginator<int, InventoryMovement>
     */
    public function paginateForIndex(?string $search = null): LengthAwarePaginator
    {
        return InventoryMovement::query()
            ->with(['warehouse:id,name,code', 'rawMaterial:id,name', 'product:id,name'])
            ->whereIn('movement_type', $this->manualMovementTypes())
            ->when($search, function ($query, string $searchTerm) {
                $query->where(function ($nestedQuery) use ($searchTerm): void {
                    $nestedQuery
                        ->where('movement_type', 'like', "%{$searchTerm}%")
                        ->orWhere('notes', 'like', "%{$searchTerm}%")
                        ->orWhereHas('warehouse', fn ($warehouseQuery) => $warehouseQuery->where('name', 'like', "%{$searchTerm}%"))
                        ->orWhereHas('rawMaterial', fn ($itemQuery) => $itemQuery->where('name', 'like', "%{$searchTerm}%"))
                        ->orWhereHas('product', fn ($itemQuery) => $itemQuery->where('name', 'like', "%{$searchTerm}%"));
                });
            })
            ->latest('moved_at')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (InventoryMovement $movement): array => [
                'id' => $movement->id,
                'item_type' => $movement->item_type,
                'item_name' => $movement->item_type === InventoryMovement::ITEM_TYPE_RAW_MATERIAL
                    ? $movement->rawMaterial?->name ?? 'Sin item'
                    : $movement->product?->name ?? 'Sin item',
                'movement_type' => $movement->movement_type,
                'direction' => $movement->direction,
                'quantity' => (string) $movement->quantity,
                'unit_cost' => $movement->unit_cost === null ? null : (string) $movement->unit_cost,
                'moved_at' => $movement->moved_at->toDateTimeString(),
                'notes' => $movement->notes,
                'warehouse' => [
                    'id' => $movement->warehouse->id,
                    'name' => $movement->warehouse->name,
                    'code' => $movement->warehouse->code,
                ],
                'can_delete' => $this->canDelete($movement),
            ]);
    }

    public function create(array $data): InventoryMovement
    {
        $normalizedData = $this->normalizePayload($data);

        $movement = InventoryMovement::query()->create($normalizedData);
        $this->financeTransactionSyncService->syncInventoryAdjustment($movement);

        return $movement;
    }

    public function update(InventoryMovement $inventoryMovement, array $data): InventoryMovement
    {
        $this->ensureManualMovement($inventoryMovement);

        $normalizedData = $this->normalizePayload($data);

        $inventoryMovement->fill($normalizedData);
        $inventoryMovement->save();
        $this->financeTransactionSyncService->syncInventoryAdjustment($inventoryMovement);

        return $inventoryMovement->refresh();
    }

    public function delete(InventoryMovement $inventoryMovement): void
    {
        $this->ensureManualMovement($inventoryMovement);
        $this->financeTransactionSyncService->deleteInventoryAdjustment($inventoryMovement);
        $inventoryMovement->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function formatForEdit(InventoryMovement $inventoryMovement): array
    {
        $this->ensureManualMovement($inventoryMovement);
        $inventoryMovement->loadMissing(['warehouse:id,name', 'rawMaterial:id,name', 'product:id,name']);

        return [
            'id' => $inventoryMovement->id,
            'warehouse_id' => $inventoryMovement->warehouse_id,
            'item_type' => $inventoryMovement->item_type,
            'item_id' => $inventoryMovement->item_id,
            'movement_type' => $inventoryMovement->movement_type,
            'direction' => $inventoryMovement->direction,
            'quantity' => (string) $inventoryMovement->quantity,
            'unit_cost' => $inventoryMovement->unit_cost === null ? '' : (string) $inventoryMovement->unit_cost,
            'moved_at' => $inventoryMovement->moved_at->format('Y-m-d\TH:i'),
            'notes' => $inventoryMovement->notes ?? '',
        ];
    }

    /**
     * @return list<array{id:int,name:string}>
     */
    public function warehouseOptions(array $includeIds = []): array
    {
        return Warehouse::query()
            ->when($includeIds !== [], function ($query) use ($includeIds): void {
                $query->where(function ($nestedQuery) use ($includeIds): void {
                    $nestedQuery->where('is_active', true)->orWhereIn('id', $includeIds);
                });
            }, function ($query): void {
                $query->where('is_active', true);
            })
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Warehouse $warehouse): array => [
                'id' => $warehouse->id,
                'name' => $warehouse->name,
            ])
            ->all();
    }

    /**
     * @return list<array{id:int,name:string}>
     */
    public function rawMaterialOptions(array $includeIds = []): array
    {
        return RawMaterial::query()
            ->when($includeIds !== [], function ($query) use ($includeIds): void {
                $query->where(function ($nestedQuery) use ($includeIds): void {
                    $nestedQuery->where('is_active', true)->orWhereIn('id', $includeIds);
                });
            }, function ($query): void {
                $query->where('is_active', true);
            })
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (RawMaterial $rawMaterial): array => [
                'id' => $rawMaterial->id,
                'name' => $rawMaterial->name,
            ])
            ->all();
    }

    /**
     * @return list<array{id:int,name:string}>
     */
    public function productOptions(array $includeIds = []): array
    {
        return Product::query()
            ->when($includeIds !== [], function ($query) use ($includeIds): void {
                $query->where(function ($nestedQuery) use ($includeIds): void {
                    $nestedQuery->where('is_active', true)->orWhereIn('id', $includeIds);
                });
            }, function ($query): void {
                $query->where('is_active', true);
            })
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
            ])
            ->all();
    }

    /**
     * @return list<string>
     */
    public function itemTypes(): array
    {
        return [
            InventoryMovement::ITEM_TYPE_RAW_MATERIAL,
            InventoryMovement::ITEM_TYPE_PRODUCT,
        ];
    }

    /**
     * @return list<string>
     */
    public function manualMovementTypes(): array
    {
        return [
            InventoryMovement::TYPE_ADJUSTMENT,
            InventoryMovement::TYPE_WASTE,
        ];
    }

    /**
     * @return list<string>
     */
    public function directions(): array
    {
        return [
            InventoryMovement::DIRECTION_IN,
            InventoryMovement::DIRECTION_OUT,
        ];
    }

    public function canDelete(InventoryMovement $inventoryMovement): bool
    {
        return in_array($inventoryMovement->movement_type, $this->manualMovementTypes(), true);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizePayload(array $data): array
    {
        if (! $this->itemExists($data['item_type'], (int) $data['item_id'])) {
            throw ValidationException::withMessages([
                'item_id' => 'El item seleccionado no existe.',
            ]);
        }

        $movementType = $data['movement_type'];
        $direction = $movementType === InventoryMovement::TYPE_WASTE
            ? InventoryMovement::DIRECTION_OUT
            : $data['direction'];

        return [
            'warehouse_id' => $data['warehouse_id'],
            'item_type' => $data['item_type'],
            'item_id' => $data['item_id'],
            'movement_type' => $movementType,
            'direction' => $direction,
            'quantity' => $data['quantity'],
            'unit_cost' => $data['unit_cost'] ?? null,
            'reference_type' => null,
            'reference_id' => null,
            'notes' => $data['notes'] ?? null,
            'moved_at' => $data['moved_at'],
        ];
    }

    private function ensureManualMovement(InventoryMovement $inventoryMovement): void
    {
        if (! in_array($inventoryMovement->movement_type, $this->manualMovementTypes(), true)) {
            throw new ModelNotFoundException('El movimiento no pertenece al modulo de ajustes.');
        }
    }

    private function itemExists(string $itemType, int $itemId): bool
    {
        return match ($itemType) {
            InventoryMovement::ITEM_TYPE_RAW_MATERIAL => RawMaterial::query()->whereKey($itemId)->exists(),
            InventoryMovement::ITEM_TYPE_PRODUCT => Product::query()->whereKey($itemId)->exists(),
            default => false,
        };
    }
}
