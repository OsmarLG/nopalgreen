<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\InventoryTransfer;
use App\Models\Product;
use App\Models\RawMaterial;
use App\Models\Warehouse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryTransferService
{
    /**
     * @return LengthAwarePaginator<int, InventoryTransfer>
     */
    public function paginateForIndex(?string $search = null): LengthAwarePaginator
    {
        return InventoryTransfer::query()
            ->with([
                'sourceWarehouse:id,name,code',
                'destinationWarehouse:id,name,code',
                'rawMaterial:id,name',
                'product:id,name',
            ])
            ->when($search, function ($query, string $searchTerm) {
                $query->where(function ($nestedQuery) use ($searchTerm): void {
                    $nestedQuery
                        ->where('notes', 'like', "%{$searchTerm}%")
                        ->orWhereHas('sourceWarehouse', fn ($warehouseQuery) => $warehouseQuery->where('name', 'like', "%{$searchTerm}%"))
                        ->orWhereHas('destinationWarehouse', fn ($warehouseQuery) => $warehouseQuery->where('name', 'like', "%{$searchTerm}%"))
                        ->orWhereHas('rawMaterial', fn ($itemQuery) => $itemQuery->where('name', 'like', "%{$searchTerm}%"))
                        ->orWhereHas('product', fn ($itemQuery) => $itemQuery->where('name', 'like', "%{$searchTerm}%"));
                });
            })
            ->latest('transferred_at')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (InventoryTransfer $inventoryTransfer): array => [
                'id' => $inventoryTransfer->id,
                'item_type' => $inventoryTransfer->item_type,
                'item_id' => $inventoryTransfer->item_id,
                'item_name' => $inventoryTransfer->item_type === InventoryMovement::ITEM_TYPE_RAW_MATERIAL
                    ? $inventoryTransfer->rawMaterial?->name ?? 'Sin item'
                    : $inventoryTransfer->product?->name ?? 'Sin item',
                'quantity' => (string) $inventoryTransfer->quantity,
                'unit_cost' => $inventoryTransfer->unit_cost === null ? null : (string) $inventoryTransfer->unit_cost,
                'transferred_at' => $inventoryTransfer->transferred_at->toDateTimeString(),
                'notes' => $inventoryTransfer->notes,
                'source_warehouse' => [
                    'id' => $inventoryTransfer->sourceWarehouse->id,
                    'name' => $inventoryTransfer->sourceWarehouse->name,
                    'code' => $inventoryTransfer->sourceWarehouse->code,
                ],
                'destination_warehouse' => [
                    'id' => $inventoryTransfer->destinationWarehouse->id,
                    'name' => $inventoryTransfer->destinationWarehouse->name,
                    'code' => $inventoryTransfer->destinationWarehouse->code,
                ],
                'can_delete' => true,
            ]);
    }

    public function create(array $data): InventoryTransfer
    {
        return DB::transaction(function () use ($data): InventoryTransfer {
            $normalizedData = $this->normalizePayload($data);
            $inventoryTransfer = InventoryTransfer::query()->create($normalizedData);
            $this->syncTransferMovements($inventoryTransfer);

            return $inventoryTransfer->refresh();
        });
    }

    public function update(InventoryTransfer $inventoryTransfer, array $data): InventoryTransfer
    {
        return DB::transaction(function () use ($inventoryTransfer, $data): InventoryTransfer {
            $normalizedData = $this->normalizePayload($data);
            $inventoryTransfer->fill($normalizedData);
            $inventoryTransfer->save();
            $this->syncTransferMovements($inventoryTransfer);

            return $inventoryTransfer->refresh();
        });
    }

    public function delete(InventoryTransfer $inventoryTransfer): void
    {
        DB::transaction(function () use ($inventoryTransfer): void {
            InventoryMovement::query()
                ->where('reference_type', InventoryTransfer::class)
                ->where('reference_id', $inventoryTransfer->id)
                ->delete();

            $inventoryTransfer->delete();
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function formatForEdit(InventoryTransfer $inventoryTransfer): array
    {
        $inventoryTransfer->loadMissing([
            'sourceWarehouse:id,name,code',
            'destinationWarehouse:id,name,code',
            'rawMaterial:id,name',
            'product:id,name',
        ]);

        return [
            'id' => $inventoryTransfer->id,
            'item_type' => $inventoryTransfer->item_type,
            'item_id' => $inventoryTransfer->item_id,
            'item_name' => $inventoryTransfer->item_type === InventoryMovement::ITEM_TYPE_RAW_MATERIAL
                ? $inventoryTransfer->rawMaterial?->name ?? 'Sin item'
                : $inventoryTransfer->product?->name ?? 'Sin item',
            'quantity' => (string) $inventoryTransfer->quantity,
            'unit_cost' => $inventoryTransfer->unit_cost === null ? '' : (string) $inventoryTransfer->unit_cost,
            'transferred_at' => $inventoryTransfer->transferred_at->format('Y-m-d\TH:i'),
            'notes' => $inventoryTransfer->notes ?? '',
            'source_warehouse' => [
                'id' => $inventoryTransfer->sourceWarehouse->id,
                'name' => $inventoryTransfer->sourceWarehouse->name,
                'code' => $inventoryTransfer->sourceWarehouse->code,
            ],
            'destination_warehouse' => [
                'id' => $inventoryTransfer->destinationWarehouse->id,
                'name' => $inventoryTransfer->destinationWarehouse->name,
                'code' => $inventoryTransfer->destinationWarehouse->code,
            ],
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
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizePayload(array $data): array
    {
        if ((int) $data['source_warehouse_id'] === (int) $data['destination_warehouse_id']) {
            throw ValidationException::withMessages([
                'destination_warehouse_id' => 'El almacen destino debe ser diferente al almacen origen.',
            ]);
        }

        if (! $this->itemExists($data['item_type'], (int) $data['item_id'])) {
            throw ValidationException::withMessages([
                'item_id' => 'El item seleccionado no existe.',
            ]);
        }

        return [
            'source_warehouse_id' => $data['source_warehouse_id'],
            'destination_warehouse_id' => $data['destination_warehouse_id'],
            'item_type' => $data['item_type'],
            'item_id' => $data['item_id'],
            'quantity' => $data['quantity'],
            'unit_cost' => $data['unit_cost'] ?? null,
            'transferred_at' => $data['transferred_at'],
            'notes' => $data['notes'] ?? null,
        ];
    }

    private function syncTransferMovements(InventoryTransfer $inventoryTransfer): void
    {
        InventoryMovement::query()
            ->where('reference_type', InventoryTransfer::class)
            ->where('reference_id', $inventoryTransfer->id)
            ->delete();

        InventoryMovement::query()->create([
            'warehouse_id' => $inventoryTransfer->source_warehouse_id,
            'item_type' => $inventoryTransfer->item_type,
            'item_id' => $inventoryTransfer->item_id,
            'movement_type' => InventoryMovement::TYPE_TRANSFER,
            'direction' => InventoryMovement::DIRECTION_OUT,
            'quantity' => $inventoryTransfer->quantity,
            'unit_cost' => $inventoryTransfer->unit_cost,
            'reference_type' => InventoryTransfer::class,
            'reference_id' => $inventoryTransfer->id,
            'notes' => $inventoryTransfer->notes,
            'moved_at' => $inventoryTransfer->transferred_at,
        ]);

        InventoryMovement::query()->create([
            'warehouse_id' => $inventoryTransfer->destination_warehouse_id,
            'item_type' => $inventoryTransfer->item_type,
            'item_id' => $inventoryTransfer->item_id,
            'movement_type' => InventoryMovement::TYPE_TRANSFER,
            'direction' => InventoryMovement::DIRECTION_IN,
            'quantity' => $inventoryTransfer->quantity,
            'unit_cost' => $inventoryTransfer->unit_cost,
            'reference_type' => InventoryTransfer::class,
            'reference_id' => $inventoryTransfer->id,
            'notes' => $inventoryTransfer->notes,
            'moved_at' => $inventoryTransfer->transferred_at,
        ]);
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
