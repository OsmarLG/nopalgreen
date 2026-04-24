<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Sale;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CustomerService
{
    /**
     * @return LengthAwarePaginator<int, Customer>
     */
    public function paginateForIndex(?string $search = null): LengthAwarePaginator
    {
        return Customer::query()
            ->when($search, function ($query, string $searchTerm) {
                $query->where(function ($nestedQuery) use ($searchTerm): void {
                    $nestedQuery
                        ->where('name', 'like', "%{$searchTerm}%")
                        ->orWhere('customer_type', 'like', "%{$searchTerm}%")
                        ->orWhere('phone', 'like', "%{$searchTerm}%")
                        ->orWhere('email', 'like', "%{$searchTerm}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(function (Customer $customer): array {
                $inUse = $this->isInUse($customer);

                return [
                    ...$customer->toArray(),
                    'in_use' => $inUse,
                    'can_delete' => ! $inUse,
                ];
            });
    }

    public function create(array $data): Customer
    {
        return Customer::query()->create($data);
    }

    public function update(Customer $customer, array $data): Customer
    {
        $customer->fill($data);
        $customer->save();

        return $customer->refresh();
    }

    public function toggleActive(Customer $customer): Customer
    {
        $customer->forceFill([
            'is_active' => ! $customer->is_active,
        ])->save();

        return $customer->refresh();
    }

    public function delete(Customer $customer): void
    {
        if ($this->isInUse($customer)) {
            throw new ModelNotFoundException('El cliente ya tiene uso y no puede eliminarse.');
        }

        $customer->delete();
    }

    public function isInUse(Customer $customer): bool
    {
        return Sale::query()
            ->where('customer_id', $customer->id)
            ->exists();
    }
}
