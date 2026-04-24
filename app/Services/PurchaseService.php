<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductPresentation;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\RawMaterial;
use App\Models\RawMaterialPresentation;
use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseService
{
    public function __construct(
        private InventoryMovementSyncService $inventoryMovementSyncService,
        private FinanceTransactionSyncService $financeTransactionSyncService,
    ) {}

    /**
     * @return LengthAwarePaginator<int, Purchase>
     */
    public function paginateForIndex(?string $search = null): LengthAwarePaginator
    {
        return Purchase::query()
            ->with(['supplier:id,name'])
            ->withCount('items')
            ->when($search, function ($query, string $searchTerm) {
                $query->where(function ($nestedQuery) use ($searchTerm): void {
                    $nestedQuery
                        ->where('folio', 'like', "%{$searchTerm}%")
                        ->orWhere('status', 'like', "%{$searchTerm}%")
                        ->orWhereHas('supplier', fn ($supplierQuery) => $supplierQuery->where('name', 'like', "%{$searchTerm}%"));
                });
            })
            ->latest('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Purchase $purchase): array => [
                'id' => $purchase->id,
                'folio' => $purchase->folio,
                'status' => $purchase->status,
                'purchased_at' => $purchase->purchased_at?->toDateTimeString(),
                'notes' => $purchase->notes,
                'supplier' => [
                    'id' => $purchase->supplier->id,
                    'name' => $purchase->supplier->name,
                ],
                'items_count' => $purchase->items_count,
                'can_delete' => $this->canDelete($purchase),
            ]);
    }

    public function create(array $data): Purchase
    {
        return DB::transaction(function () use ($data): Purchase {
            $purchase = Purchase::query()->create([
                'folio' => $this->nextFolio(),
                'supplier_id' => $data['supplier_id'],
                'status' => $data['status'],
                'purchased_at' => $data['purchased_at'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncItems($purchase, $data['items']);
            $this->inventoryMovementSyncService->syncPurchase($purchase);
            $this->financeTransactionSyncService->syncPurchase($purchase);

            return $purchase->refresh();
        });
    }

    public function update(Purchase $purchase, array $data): Purchase
    {
        return DB::transaction(function () use ($purchase, $data): Purchase {
            $purchase->fill([
                'supplier_id' => $data['supplier_id'],
                'status' => $data['status'],
                'purchased_at' => $data['purchased_at'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
            $purchase->save();

            $this->syncItems($purchase, $data['items']);
            $this->inventoryMovementSyncService->syncPurchase($purchase);
            $this->financeTransactionSyncService->syncPurchase($purchase);

            return $purchase->refresh();
        });
    }

    /**
     * @return list<array{id:int,name:string}>
     */
    public function supplierOptions(array $includeIds = []): array
    {
        return Supplier::query()
            ->when($includeIds !== [], function ($query) use ($includeIds): void {
                $query->where(function ($nestedQuery) use ($includeIds): void {
                    $nestedQuery->where('is_active', true)->orWhereIn('id', $includeIds);
                });
            }, function ($query): void {
                $query->where('is_active', true);
            })
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Supplier $supplier): array => [
                'id' => $supplier->id,
                'name' => $supplier->name,
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function rawMaterialOptions(array $includeIds = [], array $includePresentationIds = []): array
    {
        return RawMaterial::query()
            ->with(['presentations' => function ($query) use ($includePresentationIds) {
                $query
                    ->with('unit:id,name,code')
                    ->when($includePresentationIds !== [], function ($presentationQuery) use ($includePresentationIds): void {
                        $presentationQuery->where(function ($nestedQuery) use ($includePresentationIds): void {
                            $nestedQuery->where('is_active', true)->orWhereIn('id', $includePresentationIds);
                        });
                    }, function ($presentationQuery): void {
                        $presentationQuery->where('is_active', true);
                    })
                    ->orderBy('name');
            }])
            ->when($includeIds !== [], function ($query) use ($includeIds): void {
                $query->where(function ($nestedQuery) use ($includeIds): void {
                    $nestedQuery->where('is_active', true)->orWhereIn('id', $includeIds);
                });
            }, function ($query): void {
                $query->where('is_active', true);
            })
            ->orderBy('name')
            ->get()
            ->map(function (RawMaterial $rawMaterial): array {
                return [
                    'id' => $rawMaterial->id,
                    'name' => $rawMaterial->name,
                    'presentations' => $rawMaterial->presentations->map(function (RawMaterialPresentation $presentation): array {
                        return [
                            'id' => $presentation->id,
                            'name' => $presentation->name,
                            'quantity' => (string) $presentation->quantity,
                            'unit' => [
                                'id' => $presentation->unit->id,
                                'name' => $presentation->unit->name,
                                'code' => $presentation->unit->code,
                            ],
                        ];
                    })->all(),
                ];
            })
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function productOptions(array $includeIds = [], array $includePresentationIds = []): array
    {
        return Product::query()
            ->with(['presentations' => function ($query) use ($includePresentationIds) {
                $query
                    ->with('unit:id,name,code')
                    ->when($includePresentationIds !== [], function ($presentationQuery) use ($includePresentationIds): void {
                        $presentationQuery->where(function ($nestedQuery) use ($includePresentationIds): void {
                            $nestedQuery->where('is_active', true)->orWhereIn('id', $includePresentationIds);
                        });
                    }, function ($presentationQuery): void {
                        $presentationQuery->where('is_active', true);
                    })
                    ->orderBy('name');
            }])
            ->when($includeIds !== [], function ($query) use ($includeIds): void {
                $query->where(function ($nestedQuery) use ($includeIds): void {
                    $nestedQuery->where('is_active', true)->orWhereIn('id', $includeIds);
                });
            }, function ($query): void {
                $query->where('is_active', true);
            })
            ->orderBy('name')
            ->get()
            ->map(function (Product $product): array {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'presentations' => $product->presentations->map(function (ProductPresentation $presentation): array {
                        return [
                            'id' => $presentation->id,
                            'name' => $presentation->name,
                            'quantity' => (string) $presentation->quantity,
                            'unit' => [
                                'id' => $presentation->unit->id,
                                'name' => $presentation->unit->name,
                                'code' => $presentation->unit->code,
                            ],
                        ];
                    })->all(),
                ];
            })
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function itemTypes(): array
    {
        return [
            PurchaseItem::ITEM_TYPE_RAW_MATERIAL,
            PurchaseItem::ITEM_TYPE_PRODUCT,
        ];
    }

    /**
     * @return array<int, string>
     */
    public function statusOptions(): array
    {
        return Purchase::STATUSES;
    }

    /**
     * @return array<string, mixed>
     */
    public function formatForEdit(Purchase $purchase): array
    {
        return [
            'id' => $purchase->id,
            'folio' => $purchase->folio,
            'status' => $purchase->status,
            'purchased_at' => $purchase->purchased_at?->format('Y-m-d\TH:i'),
            'notes' => $purchase->notes,
            'supplier' => [
                'id' => $purchase->supplier->id,
                'name' => $purchase->supplier->name,
            ],
            'items' => $purchase->items->map(function (PurchaseItem $item): array {
                $presentation = $item->presentation_type === PurchaseItem::PRESENTATION_TYPE_RAW_MATERIAL
                    ? RawMaterialPresentation::query()->with('unit:id,name,code')->find($item->presentation_id)
                    : ProductPresentation::query()->with('unit:id,name,code')->find($item->presentation_id);

                return [
                    'id' => $item->id,
                    'item_type' => $item->item_type,
                    'item_id' => $item->item_id,
                    'item_name' => $item->item_type === PurchaseItem::ITEM_TYPE_RAW_MATERIAL
                        ? $item->rawMaterial?->name
                        : $item->product?->name,
                    'presentation_type' => $item->presentation_type,
                    'presentation_id' => $item->presentation_id,
                    'presentation_name' => $presentation?->name,
                    'quantity' => (string) $item->quantity,
                    'unit_cost' => (string) $item->unit_cost,
                    'total' => (string) $item->total,
                ];
            })->all(),
            'can_delete' => $this->canDelete($purchase),
        ];
    }

    public function delete(Purchase $purchase): void
    {
        if (! $this->canDelete($purchase)) {
            throw new ModelNotFoundException('La compra no puede eliminarse en su estado actual.');
        }

        $purchase->delete();
    }

    public function canDelete(Purchase $purchase): bool
    {
        return in_array($purchase->status, [
            Purchase::STATUS_DRAFT,
            Purchase::STATUS_CANCELLED,
        ], true);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function syncItems(Purchase $purchase, array $items): void
    {
        $purchase->items()->delete();

        foreach ($items as $index => $item) {
            if (! $this->itemExists($item['item_type'], (int) $item['item_id'])) {
                throw ValidationException::withMessages([
                    "items.{$index}.item_id" => 'El item seleccionado no existe.',
                ]);
            }

            if (! $this->presentationMatchesItem(
                $item['presentation_type'],
                (int) $item['presentation_id'],
                $item['item_type'],
                (int) $item['item_id'],
            )) {
                throw ValidationException::withMessages([
                    "items.{$index}.presentation_id" => 'La presentacion seleccionada no corresponde al item.',
                ]);
            }

            $purchase->items()->create([
                'item_type' => $item['item_type'],
                'item_id' => $item['item_id'],
                'presentation_type' => $item['presentation_type'],
                'presentation_id' => $item['presentation_id'],
                'quantity' => $item['quantity'],
                'unit_cost' => $item['unit_cost'],
                'total' => $item['total'],
            ]);
        }
    }

    private function itemExists(string $itemType, int $itemId): bool
    {
        return match ($itemType) {
            PurchaseItem::ITEM_TYPE_RAW_MATERIAL => RawMaterial::query()->whereKey($itemId)->exists(),
            PurchaseItem::ITEM_TYPE_PRODUCT => Product::query()->whereKey($itemId)->exists(),
            default => false,
        };
    }

    private function presentationMatchesItem(
        string $presentationType,
        int $presentationId,
        string $itemType,
        int $itemId,
    ): bool {
        return match ([$presentationType, $itemType]) {
            [PurchaseItem::PRESENTATION_TYPE_RAW_MATERIAL, PurchaseItem::ITEM_TYPE_RAW_MATERIAL] => RawMaterialPresentation::query()
                ->whereKey($presentationId)
                ->where('raw_material_id', $itemId)
                ->exists(),
            [PurchaseItem::PRESENTATION_TYPE_PRODUCT, PurchaseItem::ITEM_TYPE_PRODUCT] => ProductPresentation::query()
                ->whereKey($presentationId)
                ->where('product_id', $itemId)
                ->exists(),
            default => false,
        };
    }

    private function nextFolio(): string
    {
        $sequence = Purchase::query()->count() + 1;

        do {
            $folio = 'COM-'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
            $sequence++;
        } while (Purchase::query()->where('folio', $folio)->exists());

        return $folio;
    }
}
