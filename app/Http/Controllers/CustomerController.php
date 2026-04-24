<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function __construct(private CustomerService $customerService) {}

    public function index(Request $request): Response
    {
        return Inertia::render('customers/index', [
            'filters' => [
                'search' => $request->string('search')->toString(),
            ],
            'customers' => $this->customerService->paginateForIndex($request->string('search')->toString()),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('customers/create');
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $customer = $this->customerService->create($request->validated());

        return to_route('customers.edit', $customer)
            ->with('status', 'Cliente creado correctamente.');
    }

    public function edit(Customer $customer): Response
    {
        return Inertia::render('customers/edit', [
            'customerRecord' => $customer,
        ]);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $this->customerService->update($customer, $request->validated());

        return to_route('customers.edit', $customer)
            ->with('status', 'Cliente actualizado correctamente.');
    }

    public function toggleActive(Customer $customer): RedirectResponse
    {
        $this->customerService->toggleActive($customer);

        return to_route('customers.index')
            ->with('status', $customer->is_active ? 'Cliente desactivado correctamente.' : 'Cliente reactivado correctamente.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $this->customerService->delete($customer);

        return to_route('customers.index')
            ->with('status', 'Cliente eliminado correctamente.');
    }
}
