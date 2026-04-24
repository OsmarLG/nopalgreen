<?php

namespace App\Services;

use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SupplierService
{
    /**
     * @return LengthAwarePaginator<int, Supplier>
     */
    public function paginateForIndex(?string $search = null): LengthAwarePaginator
    {
        return Supplier::query()
            ->when($search, function ($query, string $searchTerm) {
                $query->where(function ($nestedQuery) use ($searchTerm): void {
                    $nestedQuery
                        ->where('name', 'like', "%{$searchTerm}%")
                        ->orWhere('contact_name', 'like', "%{$searchTerm}%")
                        ->orWhere('phone', 'like', "%{$searchTerm}%")
                        ->orWhere('email', 'like', "%{$searchTerm}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(function (Supplier $supplier): array {
                $inUse = $this->isInUse($supplier);

                return [
                    ...$supplier->toArray(),
                    'in_use' => $inUse,
                    'can_delete' => ! $inUse,
                ];
            });
    }

    public function create(array $data): Supplier
    {
        return Supplier::query()->create($data);
    }

    public function update(Supplier $supplier, array $data): Supplier
    {
        $supplier->fill($data);
        $supplier->save();

        return $supplier->refresh();
    }

    /**
     * @return list<array{id:int,name:string}>
     */
    public function options(array $includeIds = []): array
    {
        return Supplier::query()
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
            ->toArray();
    }

    public function toggleActive(Supplier $supplier): Supplier
    {
        $supplier->forceFill([
            'is_active' => ! $supplier->is_active,
        ])->save();

        return $supplier->refresh();
    }

    public function delete(Supplier $supplier): void
    {
        if ($this->isInUse($supplier)) {
            throw new ModelNotFoundException('El proveedor ya tiene uso y no puede eliminarse.');
        }

        $supplier->delete();
    }

    public function isInUse(Supplier $supplier): bool
    {
        return $supplier->rawMaterialLinks()->exists()
            || $supplier->productLinks()->exists()
            || $supplier->purchases()->exists();
    }
}
