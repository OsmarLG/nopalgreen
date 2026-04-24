<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\RawMaterial;
use App\Models\Sale;
use App\Models\Warehouse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class InventoryMovementService
{
    /**
     * @return LengthAwarePaginator<int, InventoryMovement>
     */
    public function paginateForIndex(?string $search = null, ?int $warehouseId = null): LengthAwarePaginator
    {
        return InventoryMovement::query()
            ->with(['warehouse:id,name,code,type', 'rawMaterial:id,name', 'product:id,name'])
            ->when($warehouseId, fn ($query, int $selectedWarehouseId) => $query->where('warehouse_id', $selectedWarehouseId))
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
            ->paginate(12)
            ->withQueryString()
            ->through(function (InventoryMovement $movement): array {
                $itemName = $movement->item_type === InventoryMovement::ITEM_TYPE_RAW_MATERIAL
                    ? $movement->rawMaterial?->name
                    : $movement->product?->name;

                return [
                    'id' => $movement->id,
                    'item_type' => $movement->item_type,
                    'item_name' => $itemName ?? 'Sin item',
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
                        'type' => $movement->warehouse->type,
                    ],
                    'reference_label' => $this->referenceLabel($movement),
                ];
            });
    }

    /**
     * @return array<int, array{id:int,name:string}>
     */
    public function warehouseOptions(): array
    {
        return Warehouse::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Warehouse $warehouse): array => [
                'id' => $warehouse->id,
                'name' => $warehouse->name,
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function stockSummary(?string $search = null, ?int $warehouseId = null): array
    {
        $balances = InventoryMovement::query()
            ->select([
                'warehouse_id',
                'item_type',
                'item_id',
                DB::raw("SUM(CASE WHEN direction = 'in' THEN quantity ELSE -quantity END) as balance"),
            ])
            ->when($warehouseId, fn ($query, int $selectedWarehouseId) => $query->where('warehouse_id', $selectedWarehouseId))
            ->groupBy('warehouse_id', 'item_type', 'item_id')
            ->havingRaw("SUM(CASE WHEN direction = 'in' THEN quantity ELSE -quantity END) <> 0")
            ->get();

        $warehouseIds = $balances->pluck('warehouse_id')->unique()->all();
        $rawMaterialIds = $balances->where('item_type', InventoryMovement::ITEM_TYPE_RAW_MATERIAL)->pluck('item_id')->all();
        $productIds = $balances->where('item_type', InventoryMovement::ITEM_TYPE_PRODUCT)->pluck('item_id')->all();

        $warehouses = Warehouse::query()->whereIn('id', $warehouseIds)->get()->keyBy('id');
        $rawMaterials = RawMaterial::query()->whereIn('id', $rawMaterialIds)->get()->keyBy('id');
        $products = Product::query()->whereIn('id', $productIds)->get()->keyBy('id');

        return $balances
            ->map(function ($balanceRow) use ($warehouses, $rawMaterials, $products): array {
                $warehouse = $warehouses->get($balanceRow->warehouse_id);
                $itemName = $balanceRow->item_type === InventoryMovement::ITEM_TYPE_RAW_MATERIAL
                    ? $rawMaterials->get($balanceRow->item_id)?->name
                    : $products->get($balanceRow->item_id)?->name;

                return [
                    'item_type' => $balanceRow->item_type,
                    'item_name' => $itemName ?? 'Sin item',
                    'warehouse' => [
                        'id' => $warehouse?->id ?? 0,
                        'name' => $warehouse?->name ?? 'Sin almacen',
                        'code' => $warehouse?->code ?? '',
                        'type' => $warehouse?->type ?? '',
                    ],
                    'balance' => number_format((float) $balanceRow->balance, 3, '.', ''),
                ];
            })
            ->filter(function (array $row) use ($search): bool {
                if ($search === null || $search === '') {
                    return true;
                }

                $searchTerm = mb_strtolower($search);

                return str_contains(mb_strtolower($row['item_name']), $searchTerm)
                    || str_contains(mb_strtolower($row['warehouse']['name']), $searchTerm);
            })
            ->sortBy([
                ['warehouse.name', 'asc'],
                ['item_name', 'asc'],
            ])
            ->values()
            ->all();
    }

    private function referenceLabel(InventoryMovement $movement): ?string
    {
        if ($movement->reference_type === null || $movement->reference_id === null) {
            return null;
        }

        if ($movement->reference_type === Sale::class) {
            $folio = Sale::query()
                ->whereKey($movement->reference_id)
                ->value('folio');

            return $folio ? "Venta {$folio}" : 'Venta #'.$movement->reference_id;
        }

        return class_basename($movement->reference_type).' #'.$movement->reference_id;
    }
}
