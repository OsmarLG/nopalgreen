<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\ProductionOrderConsumption;
use App\Models\PurchaseItem;
use App\Models\RawMaterial;
use App\Models\RawMaterialSupplier;
use App\Models\RecipeItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RawMaterialService
{
    /**
     * @return LengthAwarePaginator<int, RawMaterial>
     */
    public function paginateForIndex(?string $search = null): LengthAwarePaginator
    {
        return RawMaterial::query()
            ->with(['baseUnit:id,name,code', 'supplierLinks.supplier:id,name'])
            ->withCount('presentations')
            ->when($search, function ($query, string $searchTerm) {
                $query->where(function ($nestedQuery) use ($searchTerm): void {
                    $nestedQuery
                        ->where('name', 'like', "%{$searchTerm}%")
                        ->orWhere('slug', 'like', "%{$searchTerm}%")
                        ->orWhereHas('supplierLinks.supplier', fn ($supplierQuery) => $supplierQuery->where('name', 'like', "%{$searchTerm}%"));
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(function (RawMaterial $rawMaterial): array {
                $inUse = $this->isInUse($rawMaterial);

                return [
                    ...$rawMaterial->toArray(),
                    'in_use' => $inUse,
                    'can_delete' => ! $inUse,
                ];
            });
    }

    public function create(array $data): RawMaterial
    {
        return DB::transaction(function () use ($data): RawMaterial {
            $rawMaterial = RawMaterial::query()->create([
                'name' => $data['name'],
                'slug' => Str::slug($data['name']),
                'description' => $data['description'] ?? null,
                'base_unit_id' => $data['base_unit_id'],
                'is_active' => $data['is_active'],
            ]);

            $this->syncPrimarySupplier($rawMaterial, $data['supplier_id'] ?? null);

            return $rawMaterial->load(['baseUnit:id,name,code', 'supplierLinks.supplier:id,name']);
        });
    }

    public function update(RawMaterial $rawMaterial, array $data): RawMaterial
    {
        return DB::transaction(function () use ($rawMaterial, $data): RawMaterial {
            $rawMaterial->fill([
                'name' => $data['name'],
                'slug' => Str::slug($data['name']),
                'description' => $data['description'] ?? null,
                'base_unit_id' => $data['base_unit_id'],
                'is_active' => $data['is_active'],
            ]);
            $rawMaterial->save();

            $this->syncPrimarySupplier($rawMaterial, $data['supplier_id'] ?? null);

            return $rawMaterial->load(['baseUnit:id,name,code', 'supplierLinks.supplier:id,name']);
        });
    }

    private function syncPrimarySupplier(RawMaterial $rawMaterial, ?int $supplierId): void
    {
        RawMaterialSupplier::query()
            ->where('raw_material_id', $rawMaterial->id)
            ->delete();

        if ($supplierId === null) {
            return;
        }

        RawMaterialSupplier::query()->create([
            'raw_material_id' => $rawMaterial->id,
            'supplier_id' => $supplierId,
            'supplier_sku' => 'RM-'.$rawMaterial->id,
            'cost' => null,
            'is_primary' => true,
        ]);
    }

    public function toggleActive(RawMaterial $rawMaterial): RawMaterial
    {
        $rawMaterial->forceFill([
            'is_active' => ! $rawMaterial->is_active,
        ])->save();

        return $rawMaterial->refresh();
    }

    public function delete(RawMaterial $rawMaterial): void
    {
        if ($this->isInUse($rawMaterial)) {
            throw new ModelNotFoundException('La materia prima ya tiene uso y no puede eliminarse.');
        }

        $rawMaterial->delete();
    }

    public function isInUse(RawMaterial $rawMaterial): bool
    {
        return $rawMaterial->presentations()->exists()
            || $rawMaterial->supplierLinks()->exists()
            || PurchaseItem::query()
                ->where('item_type', PurchaseItem::ITEM_TYPE_RAW_MATERIAL)
                ->where('item_id', $rawMaterial->id)
                ->exists()
            || InventoryMovement::query()
                ->where('item_type', InventoryMovement::ITEM_TYPE_RAW_MATERIAL)
                ->where('item_id', $rawMaterial->id)
                ->exists()
            || RecipeItem::query()
                ->where('item_type', RecipeItem::ITEM_TYPE_RAW_MATERIAL)
                ->where('item_id', $rawMaterial->id)
                ->exists()
            || ProductionOrderConsumption::query()
                ->where('item_type', ProductionOrderConsumption::ITEM_TYPE_RAW_MATERIAL)
                ->where('item_id', $rawMaterial->id)
                ->exists();
    }
}
