<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductionOrderConsumption;
use App\Models\ProductionOrderOutput;
use App\Models\ProductSupplier;
use App\Models\PurchaseItem;
use App\Models\RecipeItem;
use App\Models\SaleItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductService
{
    /**
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginateForIndex(?string $search = null): LengthAwarePaginator
    {
        return Product::query()
            ->with(['baseUnit:id,name,code', 'supplierLinks.supplier:id,name'])
            ->withCount(['presentations', 'recipes'])
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
            ->through(function (Product $product): array {
                $inUse = $this->isInUse($product);

                return [
                    ...$product->toArray(),
                    'in_use' => $inUse,
                    'can_delete' => ! $inUse,
                ];
            });
    }

    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data): Product {
            $product = Product::query()->create([
                'name' => $data['name'],
                'slug' => Str::slug($data['name']),
                'description' => $data['description'] ?? null,
                'base_unit_id' => $data['base_unit_id'],
                'supply_source' => $data['supply_source'],
                'product_type' => $data['product_type'],
                'sale_price' => $data['sale_price'],
                'is_active' => $data['is_active'],
            ]);

            $this->syncPrimarySupplier($product, $data['supplier_id'] ?? null);

            return $product->load(['baseUnit:id,name,code', 'supplierLinks.supplier:id,name']);
        });
    }

    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data): Product {
            $product->fill([
                'name' => $data['name'],
                'slug' => Str::slug($data['name']),
                'description' => $data['description'] ?? null,
                'base_unit_id' => $data['base_unit_id'],
                'supply_source' => $data['supply_source'],
                'product_type' => $data['product_type'],
                'sale_price' => $data['sale_price'],
                'is_active' => $data['is_active'],
            ]);
            $product->save();

            $this->syncPrimarySupplier($product, $data['supplier_id'] ?? null);

            return $product->load(['baseUnit:id,name,code', 'supplierLinks.supplier:id,name']);
        });
    }

    private function syncPrimarySupplier(Product $product, ?int $supplierId): void
    {
        ProductSupplier::query()
            ->where('product_id', $product->id)
            ->delete();

        if ($supplierId === null) {
            return;
        }

        ProductSupplier::query()->create([
            'product_id' => $product->id,
            'supplier_id' => $supplierId,
            'supplier_sku' => 'PR-'.$product->id,
            'cost' => null,
            'is_primary' => true,
        ]);
    }

    public function toggleActive(Product $product): Product
    {
        $product->forceFill([
            'is_active' => ! $product->is_active,
        ])->save();

        return $product->refresh();
    }

    public function delete(Product $product): void
    {
        if ($this->isInUse($product)) {
            throw new ModelNotFoundException('El producto ya tiene uso y no puede eliminarse.');
        }

        $product->delete();
    }

    public function isInUse(Product $product): bool
    {
        return $product->presentations()->exists()
            || $product->supplierLinks()->exists()
            || $product->recipes()->exists()
            || $product->productionOrders()->exists()
            || PurchaseItem::query()
                ->where('item_type', PurchaseItem::ITEM_TYPE_PRODUCT)
                ->where('item_id', $product->id)
                ->exists()
            || InventoryMovement::query()
                ->where('item_type', InventoryMovement::ITEM_TYPE_PRODUCT)
                ->where('item_id', $product->id)
                ->exists()
            || RecipeItem::query()
                ->where('item_type', RecipeItem::ITEM_TYPE_PRODUCT)
                ->where('item_id', $product->id)
                ->exists()
            || ProductionOrderConsumption::query()
                ->where('item_type', ProductionOrderConsumption::ITEM_TYPE_PRODUCT)
                ->where('item_id', $product->id)
                ->exists()
            || ProductionOrderOutput::query()
                ->where('product_id', $product->id)
                ->exists()
            || SaleItem::query()
                ->where('product_id', $product->id)
                ->exists();
    }
}
