<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductPresentation;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function __construct(
        private SaleInventorySyncService $saleInventorySyncService,
        private FinanceTransactionSyncService $financeTransactionSyncService,
    ) {}

    /**
     * @return LengthAwarePaginator<int, Sale>
     */
    public function paginateForIndex(User $user, ?string $search = null): LengthAwarePaginator
    {
        return Sale::query()
            ->with(['customer:id,name', 'deliveryUser:id,name'])
            ->withCount('items')
            ->when($this->shouldRestrictToDeliveryUser($user), function ($query) use ($user): void {
                $query
                    ->where('delivery_user_id', $user->id)
                    ->whereIn('status', [Sale::STATUS_ASSIGNED, Sale::STATUS_COMPLETED]);
            })
            ->when($search, function ($query, string $searchTerm) {
                $query->where(function ($nestedQuery) use ($searchTerm): void {
                    $nestedQuery
                        ->where('folio', 'like', "%{$searchTerm}%")
                        ->orWhere('sale_type', 'like', "%{$searchTerm}%")
                        ->orWhere('status', 'like', "%{$searchTerm}%")
                        ->orWhereHas('customer', fn ($customerQuery) => $customerQuery->where('name', 'like', "%{$searchTerm}%"))
                        ->orWhereHas('deliveryUser', fn ($userQuery) => $userQuery->where('name', 'like', "%{$searchTerm}%"));
                });
            })
            ->latest('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Sale $sale): array => [
                'id' => $sale->id,
                'folio' => $sale->folio,
                'sale_type' => $sale->sale_type,
                'status' => $sale->status,
                'sale_date' => $sale->sale_date?->toDateTimeString(),
                'delivery_date' => $sale->delivery_date?->toDateTimeString(),
                'completed_at' => $sale->completed_at?->toDateTimeString(),
                'subtotal' => (string) $sale->subtotal,
                'discount_total' => (string) $sale->discount_total,
                'total' => (string) $sale->total,
                'notes' => $sale->notes,
                'customer' => $sale->customer ? [
                    'id' => $sale->customer->id,
                    'name' => $sale->customer->name,
                ] : null,
                'delivery_user' => $sale->deliveryUser ? [
                    'id' => $sale->deliveryUser->id,
                    'name' => $sale->deliveryUser->name,
                ] : null,
                'items_count' => $sale->items_count,
                'can_delete' => $this->canDelete($sale),
            ]);
    }

    public function canAccess(User $user, Sale $sale): bool
    {
        if (! $this->shouldRestrictToDeliveryUser($user)) {
            return true;
        }

        return $sale->delivery_user_id === $user->id
            && in_array($sale->status, [Sale::STATUS_ASSIGNED, Sale::STATUS_COMPLETED], true);
    }

    public function create(array $data): Sale
    {
        return DB::transaction(function () use ($data): Sale {
            $normalizedData = $this->normalizeSaleData($data);

            $sale = Sale::query()->create([
                'folio' => $this->nextFolio(),
                'customer_id' => $normalizedData['customer_id'] ?? null,
                'delivery_user_id' => $normalizedData['delivery_user_id'] ?? null,
                'sale_type' => $normalizedData['sale_type'],
                'status' => $normalizedData['status'],
                'sale_date' => $normalizedData['sale_date'] ?? null,
                'delivery_date' => $normalizedData['delivery_date'] ?? null,
                'completed_at' => $normalizedData['completed_at'] ?? null,
                'subtotal' => 0,
                'discount_total' => 0,
                'total' => 0,
                'notes' => $normalizedData['notes'] ?? null,
            ]);

            $totals = $this->syncItems($sale, $normalizedData['items'], $normalizedData['sale_type'], $normalizedData['status']);
            $sale->forceFill($totals)->save();
            $this->saleInventorySyncService->sync($sale);
            $this->financeTransactionSyncService->syncSale($sale);

            return $sale->refresh();
        });
    }

    public function update(Sale $sale, array $data): Sale
    {
        return DB::transaction(function () use ($sale, $data): Sale {
            $normalizedData = $this->normalizeSaleData($data);

            $sale->fill([
                'customer_id' => $normalizedData['customer_id'] ?? null,
                'delivery_user_id' => $normalizedData['delivery_user_id'] ?? null,
                'sale_type' => $normalizedData['sale_type'],
                'status' => $normalizedData['status'],
                'sale_date' => $normalizedData['sale_date'] ?? null,
                'delivery_date' => $normalizedData['delivery_date'] ?? null,
                'completed_at' => $normalizedData['completed_at'] ?? null,
                'notes' => $normalizedData['notes'] ?? null,
            ]);
            $sale->save();

            $totals = $this->syncItems($sale, $normalizedData['items'], $normalizedData['sale_type'], $normalizedData['status']);
            $sale->forceFill($totals)->save();
            $this->saleInventorySyncService->sync($sale);
            $this->financeTransactionSyncService->syncSale($sale);

            return $sale->refresh();
        });
    }

    /**
     * @return list<array{id:int,name:string}>
     */
    public function customerOptions(array $includeIds = []): array
    {
        return Customer::query()
            ->when($includeIds !== [], function ($query) use ($includeIds): void {
                $query->where(function ($nestedQuery) use ($includeIds): void {
                    $nestedQuery->where('is_active', true)->orWhereIn('id', $includeIds);
                });
            }, function ($query): void {
                $query->where('is_active', true);
            })
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();
    }

    /**
     * @return list<array{id:int,name:string}>
     */
    public function deliveryUserOptions(array $includeIds = []): array
    {
        return User::query()
            ->select('users.id', 'users.name')
            ->join('model_has_roles', function ($join): void {
                $join->on('model_has_roles.model_id', '=', 'users.id')
                    ->where('model_has_roles.model_type', User::class);
            })
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('roles.name', 'repartidor')
            ->when($includeIds !== [], fn ($query) => $query->orWhereIn('users.id', $includeIds))
            ->orderBy('users.name')
            ->distinct()
            ->get(['users.id', 'users.name'])
            ->toArray();
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
                    'sale_price' => (string) $product->sale_price,
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
    public function typeOptions(): array
    {
        return Sale::TYPES;
    }

    /**
     * @return array<int, string>
     */
    public function statusOptions(): array
    {
        return Sale::STATUSES;
    }

    /**
     * @return array<string, mixed>
     */
    public function formatForEdit(Sale $sale): array
    {
        return [
            'id' => $sale->id,
            'folio' => $sale->folio,
            'sale_type' => $sale->sale_type,
            'status' => $sale->status,
            'sale_date' => $sale->sale_date?->format('Y-m-d\TH:i'),
            'delivery_date' => $sale->delivery_date?->format('Y-m-d\TH:i'),
            'completed_at' => $sale->completed_at?->format('Y-m-d\TH:i'),
            'subtotal' => (string) $sale->subtotal,
            'discount_total' => (string) $sale->discount_total,
            'total' => (string) $sale->total,
            'notes' => $sale->notes,
            'customer' => $sale->customer ? [
                'id' => $sale->customer->id,
                'name' => $sale->customer->name,
            ] : null,
            'delivery_user' => $sale->deliveryUser ? [
                'id' => $sale->deliveryUser->id,
                'name' => $sale->deliveryUser->name,
            ] : null,
            'items' => $sale->items->map(function (SaleItem $item): array {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product?->name ?? 'Sin producto',
                    'presentation_id' => $item->presentation_id,
                    'presentation_name' => $item->presentation?->name,
                    'quantity' => (string) $item->quantity,
                    'sold_quantity' => (string) $item->sold_quantity,
                    'returned_quantity' => (string) $item->returned_quantity,
                    'catalog_price' => (string) $item->catalog_price,
                    'unit_price' => (string) $item->unit_price,
                    'discount_total' => (string) $item->discount_total,
                    'line_total' => (string) $item->line_total,
                    'discount_note' => $item->discount_note,
                ];
            })->all(),
            'can_delete' => $this->canDelete($sale),
        ];
    }

    public function delete(Sale $sale): void
    {
        if (! $this->canDelete($sale)) {
            throw new ModelNotFoundException('La venta no puede eliminarse en su estado actual.');
        }

        $sale->delete();
    }

    public function canDelete(Sale $sale): bool
    {
        return in_array($sale->status, [Sale::STATUS_DRAFT, Sale::STATUS_CANCELLED], true);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array{subtotal:float,discount_total:float,total:float}
     */
    private function syncItems(Sale $sale, array $items, string $saleType, string $status): array
    {
        $sale->items()->delete();

        $subtotal = 0.0;
        $discountTotal = 0.0;
        $total = 0.0;

        foreach ($items as $index => $item) {
            $product = Product::query()->find($item['product_id']);

            if ($product === null) {
                throw ValidationException::withMessages([
                    "items.{$index}.product_id" => 'El producto seleccionado no existe.',
                ]);
            }

            $presentation = ProductPresentation::query()
                ->whereKey($item['presentation_id'])
                ->where('product_id', $product->id)
                ->first();

            if ($presentation === null) {
                throw ValidationException::withMessages([
                    "items.{$index}.presentation_id" => 'La presentacion seleccionada no corresponde al producto.',
                ]);
            }

            $normalized = $this->normalizeItemQuantities($item, $saleType, $status, $index);
            $catalogPrice = (float) $item['catalog_price'];
            $unitPrice = (float) $item['unit_price'];
            $soldQuantity = (float) $normalized['sold_quantity'];
            $lineSubtotal = round($catalogPrice * $soldQuantity, 2);
            $lineTotal = round($unitPrice * $soldQuantity, 2);
            $itemDiscountTotal = round(max($lineSubtotal - $lineTotal, 0), 2);

            if ($unitPrice < $catalogPrice && blank($item['discount_note'] ?? null)) {
                throw ValidationException::withMessages([
                    "items.{$index}.discount_note" => 'Debes registrar una nota cuando el precio final es menor al precio de venta.',
                ]);
            }

            $sale->items()->create([
                'product_id' => $product->id,
                'presentation_id' => $presentation->id,
                'quantity' => $normalized['quantity'],
                'sold_quantity' => $normalized['sold_quantity'],
                'returned_quantity' => $normalized['returned_quantity'],
                'catalog_price' => $catalogPrice,
                'unit_price' => $unitPrice,
                'discount_total' => $itemDiscountTotal,
                'line_total' => $lineTotal,
                'discount_note' => $item['discount_note'] ?? null,
            ]);

            $subtotal += $lineSubtotal;
            $discountTotal += $itemDiscountTotal;
            $total += $lineTotal;
        }

        return [
            'subtotal' => round($subtotal, 2),
            'discount_total' => round($discountTotal, 2),
            'total' => round($total, 2),
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array{quantity:float,sold_quantity:float,returned_quantity:float}
     */
    private function normalizeItemQuantities(array $item, string $saleType, string $status, int $index): array
    {
        $quantity = round((float) $item['quantity'], 3);

        if ($saleType === Sale::TYPE_DIRECT) {
            return [
                'quantity' => $quantity,
                'sold_quantity' => $quantity,
                'returned_quantity' => 0.0,
            ];
        }

        if ($status !== Sale::STATUS_COMPLETED) {
            return [
                'quantity' => $quantity,
                'sold_quantity' => 0.0,
                'returned_quantity' => 0.0,
            ];
        }

        $soldQuantity = round((float) $item['sold_quantity'], 3);
        $returnedQuantity = round((float) $item['returned_quantity'], 3);

        if (round($soldQuantity + $returnedQuantity, 3) !== $quantity) {
            throw ValidationException::withMessages([
                "items.{$index}.sold_quantity" => 'La cantidad vendida y devuelta debe coincidir con la cantidad enviada.',
            ]);
        }

        return [
            'quantity' => $quantity,
            'sold_quantity' => $soldQuantity,
            'returned_quantity' => $returnedQuantity,
        ];
    }

    private function nextFolio(): string
    {
        $sequence = Sale::query()->count() + 1;

        do {
            $folio = 'VTA-'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
            $sequence++;
        } while (Sale::query()->where('folio', $folio)->exists());

        return $folio;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeSaleData(array $data): array
    {
        if ($data['sale_type'] === Sale::TYPE_DIRECT) {
            $data['delivery_user_id'] = null;
            $data['delivery_date'] = null;
        }

        return $data;
    }

    private function shouldRestrictToDeliveryUser(User $user): bool
    {
        return $user->hasRole('repartidor') && ! $user->hasAnyRole(['master', 'admin']);
    }
}
