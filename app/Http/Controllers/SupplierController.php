<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\Supplier;
use App\Services\SupplierService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    public function __construct(private SupplierService $supplierService) {}

    public function index(Request $request): Response
    {
        return Inertia::render('suppliers/index', [
            'filters' => [
                'search' => $request->string('search')->toString(),
            ],
            'suppliers' => $this->supplierService->paginateForIndex($request->string('search')->toString()),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('suppliers/create');
    }

    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        $supplier = $this->supplierService->create($request->validated());

        return to_route('suppliers.edit', $supplier)
            ->with('status', 'Proveedor creado correctamente.');
    }

    public function edit(Supplier $supplier): Response
    {
        return Inertia::render('suppliers/edit', [
            'supplierRecord' => $supplier,
        ]);
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $this->supplierService->update($supplier, $request->validated());

        return to_route('suppliers.edit', $supplier)
            ->with('status', 'Proveedor actualizado correctamente.');
    }

    public function toggleActive(Supplier $supplier): RedirectResponse
    {
        $this->supplierService->toggleActive($supplier);

        return to_route('suppliers.index')
            ->with('status', $supplier->is_active ? 'Proveedor desactivado correctamente.' : 'Proveedor reactivado correctamente.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $this->supplierService->delete($supplier);

        return to_route('suppliers.index')
            ->with('status', 'Proveedor eliminado correctamente.');
    }
}
