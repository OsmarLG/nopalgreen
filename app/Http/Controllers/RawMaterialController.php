<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRawMaterialRequest;
use App\Http\Requests\UpdateRawMaterialRequest;
use App\Models\RawMaterial;
use App\Services\RawMaterialService;
use App\Services\SupplierService;
use App\Services\UnitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RawMaterialController extends Controller
{
    public function __construct(
        private RawMaterialService $rawMaterialService,
        private UnitService $unitService,
        private SupplierService $supplierService,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('raw-materials/index', [
            'filters' => [
                'search' => $request->string('search')->toString(),
            ],
            'rawMaterials' => $this->rawMaterialService->paginateForIndex($request->string('search')->toString()),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('raw-materials/create', [
            'units' => $this->unitService->options(),
            'suppliers' => $this->supplierService->options(),
        ]);
    }

    public function store(StoreRawMaterialRequest $request): RedirectResponse
    {
        $rawMaterial = $this->rawMaterialService->create($request->validated());

        return to_route('raw-materials.edit', $rawMaterial)
            ->with('status', 'Materia prima creada correctamente.');
    }

    public function edit(RawMaterial $rawMaterial): Response
    {
        $rawMaterial->load(['baseUnit:id,name,code', 'supplierLinks.supplier:id,name']);

        return Inertia::render('raw-materials/edit', [
            'rawMaterialRecord' => $rawMaterial,
            'units' => $this->unitService->options([$rawMaterial->base_unit_id]),
            'suppliers' => $this->supplierService->options($rawMaterial->supplierLinks->pluck('supplier_id')->filter()->all()),
            'selectedSupplierId' => $rawMaterial->supplierLinks->first()?->supplier_id,
        ]);
    }

    public function update(UpdateRawMaterialRequest $request, RawMaterial $rawMaterial): RedirectResponse
    {
        $this->rawMaterialService->update($rawMaterial, $request->validated());

        return to_route('raw-materials.edit', $rawMaterial)
            ->with('status', 'Materia prima actualizada correctamente.');
    }

    public function toggleActive(RawMaterial $rawMaterial): RedirectResponse
    {
        $this->rawMaterialService->toggleActive($rawMaterial);

        return to_route('raw-materials.index')
            ->with('status', $rawMaterial->is_active ? 'Materia prima desactivada correctamente.' : 'Materia prima reactivada correctamente.');
    }

    public function destroy(RawMaterial $rawMaterial): RedirectResponse
    {
        $this->rawMaterialService->delete($rawMaterial);

        return to_route('raw-materials.index')
            ->with('status', 'Materia prima eliminada correctamente.');
    }
}
