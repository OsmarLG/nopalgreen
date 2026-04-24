<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductPresentation;
use App\Models\PurchaseItem;
use App\Models\RawMaterial;
use App\Models\RawMaterialPresentation;
use App\Models\SaleItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class PresentationService
{
    public const OWNER_TYPE_RAW_MATERIAL = 'raw_material';

    public const OWNER_TYPE_PRODUCT = 'product';

    /**
     * @return array<int, string>
     */
    public static function ownerTypes(): array
    {
        return [
            self::OWNER_TYPE_RAW_MATERIAL,
            self::OWNER_TYPE_PRODUCT,
        ];
    }

    public function paginateForIndex(?string $search = null): LengthAwarePaginator
    {
        $presentations = $this->presentationCollection($search);

        $currentPage = Paginator::resolveCurrentPage('page');
        $perPage = 10;
        $total = $presentations->count();
        $results = $presentations->slice(($currentPage - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $results,
            $total,
            $perPage,
            $currentPage,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ],
        );
    }

    /**
     * @return array<int, array{id:int, name:string}>
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
     * @return array<int, array{id:int, name:string}>
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

    public function create(array $data): RawMaterialPresentation|ProductPresentation
    {
        return match ($data['owner_type']) {
            self::OWNER_TYPE_RAW_MATERIAL => RawMaterialPresentation::query()->create([
                'raw_material_id' => $data['owner_id'],
                'name' => $data['name'],
                'quantity' => $data['quantity'],
                'unit_id' => $data['unit_id'],
                'barcode' => $data['barcode'] ?? null,
                'is_active' => $data['is_active'],
            ]),
            self::OWNER_TYPE_PRODUCT => ProductPresentation::query()->create([
                'product_id' => $data['owner_id'],
                'name' => $data['name'],
                'quantity' => $data['quantity'],
                'unit_id' => $data['unit_id'],
                'barcode' => $data['barcode'] ?? null,
                'is_active' => $data['is_active'],
            ]),
            default => throw ValidationException::withMessages([
                'owner_type' => 'Tipo de presentacion invalido.',
            ]),
        };
    }

    public function findForEdit(string $ownerType, int $presentationId): RawMaterialPresentation|ProductPresentation
    {
        return $this->presentationQuery($ownerType)
            ->with($this->editRelations($ownerType))
            ->findOrFail($presentationId);
    }

    public function update(string $ownerType, int $presentationId, array $data): RawMaterialPresentation|ProductPresentation
    {
        $presentation = $this->presentationQuery($ownerType)->findOrFail($presentationId);

        match ($ownerType) {
            self::OWNER_TYPE_RAW_MATERIAL => $presentation->fill([
                'raw_material_id' => $data['owner_id'],
                'name' => $data['name'],
                'quantity' => $data['quantity'],
                'unit_id' => $data['unit_id'],
                'barcode' => $data['barcode'] ?? null,
                'is_active' => $data['is_active'],
            ])->save(),
            self::OWNER_TYPE_PRODUCT => $presentation->fill([
                'product_id' => $data['owner_id'],
                'name' => $data['name'],
                'quantity' => $data['quantity'],
                'unit_id' => $data['unit_id'],
                'barcode' => $data['barcode'] ?? null,
                'is_active' => $data['is_active'],
            ])->save(),
            default => throw ValidationException::withMessages([
                'owner_type' => 'Tipo de presentacion invalido.',
            ]),
        };

        return $presentation->load($this->editRelations($ownerType));
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function presentationCollection(?string $search = null): Collection
    {
        $rawMaterialPresentations = RawMaterialPresentation::query()
            ->with(['rawMaterial:id,name', 'unit:id,name,code'])
            ->when($search, function ($query, string $searchTerm) {
                $query->where(function ($nestedQuery) use ($searchTerm): void {
                    $nestedQuery
                        ->where('name', 'like', "%{$searchTerm}%")
                        ->orWhere('barcode', 'like', "%{$searchTerm}%")
                        ->orWhereHas('rawMaterial', fn ($rawMaterialQuery) => $rawMaterialQuery->where('name', 'like', "%{$searchTerm}%"));
                });
            })
            ->get()
            ->map(function (RawMaterialPresentation $presentation): array {
                return [
                    'id' => $presentation->id,
                    'owner_type' => self::OWNER_TYPE_RAW_MATERIAL,
                    'owner_type_label' => 'Materia Prima',
                    'owner_id' => $presentation->raw_material_id,
                    'owner_name' => $presentation->rawMaterial->name,
                    'name' => $presentation->name,
                    'quantity' => (string) $presentation->quantity,
                    'barcode' => $presentation->barcode,
                    'is_active' => $presentation->is_active,
                    'in_use' => $this->isInUse(self::OWNER_TYPE_RAW_MATERIAL, $presentation->id),
                    'can_delete' => ! $this->isInUse(self::OWNER_TYPE_RAW_MATERIAL, $presentation->id),
                    'unit' => [
                        'id' => $presentation->unit->id,
                        'name' => $presentation->unit->name,
                        'code' => $presentation->unit->code,
                    ],
                ];
            });

        $productPresentations = ProductPresentation::query()
            ->with(['product:id,name', 'unit:id,name,code'])
            ->when($search, function ($query, string $searchTerm) {
                $query->where(function ($nestedQuery) use ($searchTerm): void {
                    $nestedQuery
                        ->where('name', 'like', "%{$searchTerm}%")
                        ->orWhere('barcode', 'like', "%{$searchTerm}%")
                        ->orWhereHas('product', fn ($productQuery) => $productQuery->where('name', 'like', "%{$searchTerm}%"));
                });
            })
            ->get()
            ->map(function (ProductPresentation $presentation): array {
                return [
                    'id' => $presentation->id,
                    'owner_type' => self::OWNER_TYPE_PRODUCT,
                    'owner_type_label' => 'Producto',
                    'owner_id' => $presentation->product_id,
                    'owner_name' => $presentation->product->name,
                    'name' => $presentation->name,
                    'quantity' => (string) $presentation->quantity,
                    'barcode' => $presentation->barcode,
                    'is_active' => $presentation->is_active,
                    'in_use' => $this->isInUse(self::OWNER_TYPE_PRODUCT, $presentation->id),
                    'can_delete' => ! $this->isInUse(self::OWNER_TYPE_PRODUCT, $presentation->id),
                    'unit' => [
                        'id' => $presentation->unit->id,
                        'name' => $presentation->unit->name,
                        'code' => $presentation->unit->code,
                    ],
                ];
            });

        return $rawMaterialPresentations
            ->concat($productPresentations)
            ->sortBy([
                ['owner_type_label', 'asc'],
                ['owner_name', 'asc'],
                ['name', 'asc'],
            ])
            ->values();
    }

    /**
     * @return Builder<RawMaterialPresentation|ProductPresentation>
     */
    private function presentationQuery(string $ownerType)
    {
        return match ($ownerType) {
            self::OWNER_TYPE_RAW_MATERIAL => RawMaterialPresentation::query(),
            self::OWNER_TYPE_PRODUCT => ProductPresentation::query(),
            default => throw ValidationException::withMessages([
                'owner_type' => 'Tipo de presentacion invalido.',
            ]),
        };
    }

    /**
     * @return array<int, string>
     */
    private function editRelations(string $ownerType): array
    {
        return match ($ownerType) {
            self::OWNER_TYPE_RAW_MATERIAL => ['rawMaterial:id,name', 'unit:id,name,code'],
            self::OWNER_TYPE_PRODUCT => ['product:id,name', 'unit:id,name,code'],
            default => [],
        };
    }

    public function toggleActive(string $ownerType, int $presentationId): RawMaterialPresentation|ProductPresentation
    {
        $presentation = $this->presentationQuery($ownerType)->findOrFail($presentationId);
        $presentation->forceFill([
            'is_active' => ! $presentation->is_active,
        ])->save();

        return $presentation->refresh();
    }

    public function delete(string $ownerType, int $presentationId): void
    {
        if ($this->isInUse($ownerType, $presentationId)) {
            throw new ModelNotFoundException('La presentacion ya tiene uso y no puede eliminarse.');
        }

        $this->presentationQuery($ownerType)->findOrFail($presentationId)->delete();
    }

    public function isInUse(string $ownerType, int $presentationId): bool
    {
        return match ($ownerType) {
            self::OWNER_TYPE_RAW_MATERIAL => PurchaseItem::query()
                ->where('presentation_type', PurchaseItem::PRESENTATION_TYPE_RAW_MATERIAL)
                ->where('presentation_id', $presentationId)
                ->exists(),
            self::OWNER_TYPE_PRODUCT => PurchaseItem::query()
                ->where('presentation_type', PurchaseItem::PRESENTATION_TYPE_PRODUCT)
                ->where('presentation_id', $presentationId)
                ->exists()
                || SaleItem::query()
                    ->where('presentation_id', $presentationId)
                    ->exists(),
            default => false,
        };
    }
}
