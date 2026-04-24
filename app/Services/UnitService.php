<?php

namespace App\Services;

use App\Models\ProductionOrder;
use App\Models\ProductionOrderConsumption;
use App\Models\ProductionOrderOutput;
use App\Models\ProductPresentation;
use App\Models\RawMaterialPresentation;
use App\Models\Recipe;
use App\Models\RecipeItem;
use App\Models\Unit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UnitService
{
    /**
     * @return LengthAwarePaginator<int, Unit>
     */
    public function paginateForIndex(?string $search = null): LengthAwarePaginator
    {
        return Unit::query()
            ->when($search, function ($query, string $searchTerm) {
                $query->where(function ($nestedQuery) use ($searchTerm): void {
                    $nestedQuery
                        ->where('name', 'like', "%{$searchTerm}%")
                        ->orWhere('code', 'like', "%{$searchTerm}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(function (Unit $unit): array {
                $inUse = $this->isInUse($unit);

                return [
                    ...$unit->toArray(),
                    'in_use' => $inUse,
                    'can_delete' => ! $inUse,
                ];
            });
    }

    public function create(array $data): Unit
    {
        return Unit::query()->create($data);
    }

    public function update(Unit $unit, array $data): Unit
    {
        $unit->fill($data);
        $unit->save();

        return $unit->refresh();
    }

    /**
     * @return list<array{id:int,name:string,code:string}>
     */
    public function options(array $includeIds = []): array
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

    public function toggleActive(Unit $unit): Unit
    {
        $unit->forceFill([
            'is_active' => ! $unit->is_active,
        ])->save();

        return $unit->refresh();
    }

    public function delete(Unit $unit): void
    {
        if ($this->isInUse($unit)) {
            throw new ModelNotFoundException('La unidad ya tiene uso y no puede eliminarse.');
        }

        $unit->delete();
    }

    public function isInUse(Unit $unit): bool
    {
        return $unit->rawMaterials()->exists()
            || $unit->products()->exists()
            || RawMaterialPresentation::query()->where('unit_id', $unit->id)->exists()
            || ProductPresentation::query()->where('unit_id', $unit->id)->exists()
            || Recipe::query()->where('yield_unit_id', $unit->id)->exists()
            || RecipeItem::query()->where('unit_id', $unit->id)->exists()
            || ProductionOrder::query()->where('unit_id', $unit->id)->exists()
            || ProductionOrderConsumption::query()->where('unit_id', $unit->id)->exists()
            || ProductionOrderOutput::query()->where('unit_id', $unit->id)->exists();
    }
}
