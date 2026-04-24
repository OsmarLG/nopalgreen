<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\RawMaterial;
use App\Models\Recipe;
use App\Models\RecipeItem;
use App\Models\Unit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecipeService
{
    /**
     * @return LengthAwarePaginator<int, Recipe>
     */
    public function paginateForIndex(?string $search = null): LengthAwarePaginator
    {
        return Recipe::query()
            ->with(['product:id,name', 'yieldUnit:id,name,code'])
            ->withCount('items')
            ->when($search, function ($query, string $searchTerm) {
                $query->where(function ($nestedQuery) use ($searchTerm): void {
                    $nestedQuery
                        ->where('name', 'like', "%{$searchTerm}%")
                        ->orWhereHas('product', fn ($productQuery) => $productQuery->where('name', 'like', "%{$searchTerm}%"));
                });
            })
            ->orderBy('name')
            ->orderByDesc('version')
            ->paginate(10)
            ->withQueryString()
            ->through(function (Recipe $recipe): array {
                $inUse = $this->isInUse($recipe);

                return [
                    ...$recipe->toArray(),
                    'in_use' => $inUse,
                    'can_delete' => ! $inUse,
                ];
            });
    }

    public function create(array $data): Recipe
    {
        return DB::transaction(function () use ($data): Recipe {
            $recipe = Recipe::query()->create([
                'product_id' => $data['product_id'],
                'name' => $data['name'],
                'version' => $data['version'],
                'yield_quantity' => $data['yield_quantity'],
                'yield_unit_id' => $data['yield_unit_id'],
                'is_active' => $data['is_active'],
            ]);

            $this->syncItems($recipe, $data['items']);

            return $recipe->load([
                'product:id,name',
                'yieldUnit:id,name,code',
                'items.unit:id,name,code',
                'items.rawMaterial:id,name',
                'items.product:id,name',
            ]);
        });
    }

    public function update(Recipe $recipe, array $data): Recipe
    {
        return DB::transaction(function () use ($recipe, $data): Recipe {
            $recipe->fill([
                'product_id' => $data['product_id'],
                'name' => $data['name'],
                'version' => $data['version'],
                'yield_quantity' => $data['yield_quantity'],
                'yield_unit_id' => $data['yield_unit_id'],
                'is_active' => $data['is_active'],
            ]);
            $recipe->save();

            $this->syncItems($recipe, $data['items']);

            return $recipe->load([
                'product:id,name',
                'yieldUnit:id,name,code',
                'items.unit:id,name,code',
                'items.rawMaterial:id,name',
                'items.product:id,name',
            ]);
        });
    }

    /**
     * @return list<array{id:int,name:string}>
     */
    public function productOptions(array $includeIds = []): array
    {
        return Product::query()
            ->when($includeIds !== [], function ($query) use ($includeIds): void {
                $query->where(function ($nestedQuery) use ($includeIds): void {
                    $nestedQuery
                        ->where('is_active', true)
                        ->orWhereIn('id', $includeIds);
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
     * @return list<array{id:int,name:string}>
     */
    public function rawMaterialOptions(array $includeIds = []): array
    {
        return RawMaterial::query()
            ->when($includeIds !== [], function ($query) use ($includeIds): void {
                $query->where(function ($nestedQuery) use ($includeIds): void {
                    $nestedQuery
                        ->where('is_active', true)
                        ->orWhereIn('id', $includeIds);
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
     * @return list<array{id:int,name:string,code:string}>
     */
    public function unitOptions(array $includeIds = []): array
    {
        return Unit::query()
            ->when($includeIds !== [], function ($query) use ($includeIds): void {
                $query->where(function ($nestedQuery) use ($includeIds): void {
                    $nestedQuery
                        ->where('is_active', true)
                        ->orWhereIn('id', $includeIds);
                });
            }, function ($query): void {
                $query->where('is_active', true);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->toArray();
    }

    /**
     * @return array<int, string>
     */
    public function itemTypes(): array
    {
        return [
            RecipeItem::ITEM_TYPE_RAW_MATERIAL,
            RecipeItem::ITEM_TYPE_PRODUCT,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function syncItems(Recipe $recipe, array $items): void
    {
        $recipe->items()->delete();

        foreach ($items as $index => $item) {
            if (! $this->itemExists($item['item_type'], (int) $item['item_id'])) {
                throw ValidationException::withMessages([
                    "items.{$index}.item_id" => 'El insumo seleccionado no existe.',
                ]);
            }

            $recipe->items()->create([
                'item_type' => $item['item_type'],
                'item_id' => $item['item_id'],
                'quantity' => $item['quantity'],
                'unit_id' => $item['unit_id'],
                'sort_order' => $index + 1,
            ]);
        }
    }

    private function itemExists(string $itemType, int $itemId): bool
    {
        return match ($itemType) {
            RecipeItem::ITEM_TYPE_RAW_MATERIAL => RawMaterial::query()->whereKey($itemId)->exists(),
            RecipeItem::ITEM_TYPE_PRODUCT => Product::query()->whereKey($itemId)->exists(),
            default => false,
        };
    }

    public function toggleActive(Recipe $recipe): Recipe
    {
        $recipe->forceFill([
            'is_active' => ! $recipe->is_active,
        ])->save();

        return $recipe->refresh();
    }

    public function delete(Recipe $recipe): void
    {
        if ($this->isInUse($recipe)) {
            throw new ModelNotFoundException('La receta ya tiene uso y no puede eliminarse.');
        }

        $recipe->delete();
    }

    public function isInUse(Recipe $recipe): bool
    {
        return ProductionOrder::query()->where('recipe_id', $recipe->id)->exists();
    }
}
