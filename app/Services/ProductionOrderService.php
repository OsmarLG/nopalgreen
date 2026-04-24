<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderConsumption;
use App\Models\RawMaterial;
use App\Models\Recipe;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductionOrderService
{
    public function __construct(
        private InventoryMovementSyncService $inventoryMovementSyncService,
        private FinanceTransactionSyncService $financeTransactionSyncService,
    ) {}

    /**
     * @return LengthAwarePaginator<int, ProductionOrder>
     */
    public function paginateForIndex(?string $search = null): LengthAwarePaginator
    {
        return ProductionOrder::query()
            ->with(['product:id,name', 'recipe:id,name,version', 'unit:id,name,code'])
            ->withCount('consumptions')
            ->when($search, function ($query, string $searchTerm) {
                $query->where(function ($nestedQuery) use ($searchTerm): void {
                    $nestedQuery
                        ->where('folio', 'like', "%{$searchTerm}%")
                        ->orWhere('status', 'like', "%{$searchTerm}%")
                        ->orWhereHas('product', fn ($productQuery) => $productQuery->where('name', 'like', "%{$searchTerm}%"))
                        ->orWhereHas('recipe', fn ($recipeQuery) => $recipeQuery->where('name', 'like', "%{$searchTerm}%"));
                });
            })
            ->latest('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (ProductionOrder $productionOrder): array => [
                'id' => $productionOrder->id,
                'folio' => $productionOrder->folio,
                'planned_quantity' => (string) $productionOrder->planned_quantity,
                'produced_quantity' => (string) $productionOrder->produced_quantity,
                'status' => $productionOrder->status,
                'scheduled_for' => $productionOrder->scheduled_for?->toDateTimeString(),
                'started_at' => $productionOrder->started_at?->toDateTimeString(),
                'finished_at' => $productionOrder->finished_at?->toDateTimeString(),
                'notes' => $productionOrder->notes,
                'product' => [
                    'id' => $productionOrder->product->id,
                    'name' => $productionOrder->product->name,
                ],
                'recipe' => [
                    'id' => $productionOrder->recipe->id,
                    'name' => $productionOrder->recipe->name,
                    'version' => $productionOrder->recipe->version,
                ],
                'unit' => [
                    'id' => $productionOrder->unit->id,
                    'name' => $productionOrder->unit->name,
                    'code' => $productionOrder->unit->code,
                ],
                'consumptions_count' => $productionOrder->consumptions_count,
                'can_delete' => $this->canDelete($productionOrder),
            ]);
    }

    public function create(array $data): ProductionOrder
    {
        return DB::transaction(function () use ($data): ProductionOrder {
            $productionOrder = ProductionOrder::query()->create([
                'folio' => $this->nextFolio(),
                'product_id' => $data['product_id'],
                'recipe_id' => $data['recipe_id'],
                'planned_quantity' => $data['planned_quantity'],
                'produced_quantity' => $data['produced_quantity'],
                'unit_id' => $data['unit_id'],
                'status' => $data['status'],
                'scheduled_for' => $data['scheduled_for'] ?? null,
                'started_at' => $data['started_at'] ?? null,
                'finished_at' => $data['finished_at'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncConsumptions($productionOrder, $data['consumptions']);
            $this->syncMainOutput($productionOrder);
            $this->inventoryMovementSyncService->syncProductionOrder($productionOrder);
            $this->financeTransactionSyncService->syncProductionOrder($productionOrder);

            return $productionOrder->refresh();
        });
    }

    public function update(ProductionOrder $productionOrder, array $data): ProductionOrder
    {
        return DB::transaction(function () use ($productionOrder, $data): ProductionOrder {
            $productionOrder->fill([
                'product_id' => $data['product_id'],
                'recipe_id' => $data['recipe_id'],
                'planned_quantity' => $data['planned_quantity'],
                'produced_quantity' => $data['produced_quantity'],
                'unit_id' => $data['unit_id'],
                'status' => $data['status'],
                'scheduled_for' => $data['scheduled_for'] ?? null,
                'started_at' => $data['started_at'] ?? null,
                'finished_at' => $data['finished_at'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
            $productionOrder->save();

            $this->syncConsumptions($productionOrder, $data['consumptions']);
            $this->syncMainOutput($productionOrder);
            $this->inventoryMovementSyncService->syncProductionOrder($productionOrder);
            $this->financeTransactionSyncService->syncProductionOrder($productionOrder);

            return $productionOrder->refresh();
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function recipeOptions(array $includeIds = []): array
    {
        return Recipe::query()
            ->with([
                'product:id,name',
                'yieldUnit:id,name,code',
                'items.unit:id,name,code',
                'items.rawMaterial:id,name',
                'items.product:id,name',
            ])
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
            ->orderByDesc('version')
            ->get()
            ->map(function (Recipe $recipe): array {
                return [
                    'id' => $recipe->id,
                    'name' => $recipe->name,
                    'version' => $recipe->version,
                    'product' => [
                        'id' => $recipe->product->id,
                        'name' => $recipe->product->name,
                    ],
                    'yield_quantity' => (string) $recipe->yield_quantity,
                    'yield_unit' => [
                        'id' => $recipe->yieldUnit->id,
                        'name' => $recipe->yieldUnit->name,
                        'code' => $recipe->yieldUnit->code,
                    ],
                    'items' => $recipe->items->sortBy('sort_order')->values()->map(function ($item): array {
                        return [
                            'item_type' => $item->item_type,
                            'item_id' => $item->item_id,
                            'item_name' => $item->item_type === ProductionOrderConsumption::ITEM_TYPE_RAW_MATERIAL
                                ? $item->rawMaterial?->name
                                : $item->product?->name,
                            'quantity' => (string) $item->quantity,
                            'unit' => [
                                'id' => $item->unit->id,
                                'name' => $item->unit->name,
                                'code' => $item->unit->code,
                            ],
                            'sort_order' => $item->sort_order,
                        ];
                    })->all(),
                ];
            })
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function statusOptions(): array
    {
        return ProductionOrder::STATUSES;
    }

    /**
     * @return array<string, mixed>
     */
    public function formatForEdit(ProductionOrder $productionOrder): array
    {
        return [
            'id' => $productionOrder->id,
            'folio' => $productionOrder->folio,
            'planned_quantity' => (string) $productionOrder->planned_quantity,
            'produced_quantity' => (string) $productionOrder->produced_quantity,
            'status' => $productionOrder->status,
            'scheduled_for' => $productionOrder->scheduled_for?->format('Y-m-d\TH:i'),
            'started_at' => $productionOrder->started_at?->format('Y-m-d\TH:i'),
            'finished_at' => $productionOrder->finished_at?->format('Y-m-d\TH:i'),
            'notes' => $productionOrder->notes,
            'product' => [
                'id' => $productionOrder->product->id,
                'name' => $productionOrder->product->name,
            ],
            'recipe' => [
                'id' => $productionOrder->recipe->id,
                'name' => $productionOrder->recipe->name,
                'version' => $productionOrder->recipe->version,
            ],
            'unit' => [
                'id' => $productionOrder->unit->id,
                'name' => $productionOrder->unit->name,
                'code' => $productionOrder->unit->code,
            ],
            'consumptions' => $productionOrder->consumptions->values()->map(function (ProductionOrderConsumption $consumption): array {
                return [
                    'id' => $consumption->id,
                    'item_type' => $consumption->item_type,
                    'item_id' => $consumption->item_id,
                    'item_name' => $consumption->item_type === ProductionOrderConsumption::ITEM_TYPE_RAW_MATERIAL
                        ? $consumption->rawMaterial?->name
                        : $consumption->product?->name,
                    'planned_quantity' => (string) $consumption->planned_quantity,
                    'consumed_quantity' => (string) $consumption->consumed_quantity,
                    'unit' => [
                        'id' => $consumption->unit->id,
                        'name' => $consumption->unit->name,
                        'code' => $consumption->unit->code,
                    ],
                ];
            })->all(),
            'can_delete' => $this->canDelete($productionOrder),
        ];
    }

    public function delete(ProductionOrder $productionOrder): void
    {
        if (! $this->canDelete($productionOrder)) {
            throw new ModelNotFoundException('La orden de produccion no puede eliminarse en su estado actual.');
        }

        $productionOrder->delete();
    }

    public function canDelete(ProductionOrder $productionOrder): bool
    {
        return in_array($productionOrder->status, [
            ProductionOrder::STATUS_DRAFT,
            ProductionOrder::STATUS_PLANNED,
            ProductionOrder::STATUS_CANCELLED,
        ], true);
    }

    /**
     * @param  array<int, array<string, mixed>>  $consumptions
     */
    private function syncConsumptions(ProductionOrder $productionOrder, array $consumptions): void
    {
        $productionOrder->consumptions()->delete();

        foreach ($consumptions as $index => $consumption) {
            if (! $this->itemExists($consumption['item_type'], (int) $consumption['item_id'])) {
                throw ValidationException::withMessages([
                    "consumptions.{$index}.item_id" => 'El insumo seleccionado no existe.',
                ]);
            }

            $productionOrder->consumptions()->create([
                'item_type' => $consumption['item_type'],
                'item_id' => $consumption['item_id'],
                'planned_quantity' => $consumption['planned_quantity'],
                'consumed_quantity' => $consumption['consumed_quantity'],
                'unit_id' => $consumption['unit_id'],
            ]);
        }
    }

    private function syncMainOutput(ProductionOrder $productionOrder): void
    {
        $productionOrder->outputs()->delete();

        $productionOrder->outputs()->create([
            'product_id' => $productionOrder->product_id,
            'quantity' => $productionOrder->produced_quantity,
            'unit_id' => $productionOrder->unit_id,
            'is_main_output' => true,
        ]);
    }

    private function itemExists(string $itemType, int $itemId): bool
    {
        return match ($itemType) {
            ProductionOrderConsumption::ITEM_TYPE_RAW_MATERIAL => RawMaterial::query()->whereKey($itemId)->exists(),
            ProductionOrderConsumption::ITEM_TYPE_PRODUCT => Product::query()->whereKey($itemId)->exists(),
            default => false,
        };
    }

    private function nextFolio(): string
    {
        $sequence = ProductionOrder::query()->count() + 1;

        do {
            $folio = 'OP-'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
            $sequence++;
        } while (ProductionOrder::query()->where('folio', $folio)->exists());

        return $folio;
    }
}
