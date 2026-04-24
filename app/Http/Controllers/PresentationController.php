<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePresentationRequest;
use App\Http\Requests\UpdatePresentationRequest;
use App\Services\PresentationService;
use App\Services\UnitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PresentationController extends Controller
{
    public function __construct(
        private PresentationService $presentationService,
        private UnitService $unitService,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('presentations/index', [
            'filters' => [
                'search' => $request->string('search')->toString(),
            ],
            'presentations' => $this->presentationService->paginateForIndex($request->string('search')->toString()),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('presentations/create', [
            'units' => $this->unitService->options(),
            'rawMaterials' => $this->presentationService->rawMaterialOptions(),
            'products' => $this->presentationService->productOptions(),
            'ownerTypes' => PresentationService::ownerTypes(),
        ]);
    }

    public function store(StorePresentationRequest $request): RedirectResponse
    {
        $presentation = $this->presentationService->create($request->validated());
        $ownerType = $request->string('owner_type')->toString();

        return to_route('presentations.edit', [
            'ownerType' => $ownerType,
            'presentation' => $presentation->id,
        ])->with('status', 'Presentacion creada correctamente.');
    }

    public function edit(string $ownerType, int $presentation): Response
    {
        $presentationRecord = $this->presentationService->findForEdit($ownerType, $presentation);

        return Inertia::render('presentations/edit', [
            'presentationRecord' => [
                'id' => $presentationRecord->id,
                'owner_type' => $ownerType,
                'owner_type_label' => $ownerType === PresentationService::OWNER_TYPE_RAW_MATERIAL ? 'Materia Prima' : 'Producto',
                'owner_id' => $ownerType === PresentationService::OWNER_TYPE_RAW_MATERIAL
                    ? $presentationRecord->raw_material_id
                    : $presentationRecord->product_id,
                'owner_name' => $ownerType === PresentationService::OWNER_TYPE_RAW_MATERIAL
                    ? $presentationRecord->rawMaterial->name
                    : $presentationRecord->product->name,
                'name' => $presentationRecord->name,
                'quantity' => (string) $presentationRecord->quantity,
                'barcode' => $presentationRecord->barcode,
                'is_active' => $presentationRecord->is_active,
                'unit' => [
                    'id' => $presentationRecord->unit->id,
                    'name' => $presentationRecord->unit->name,
                    'code' => $presentationRecord->unit->code,
                ],
            ],
            'units' => $this->unitService->options([$presentationRecord->unit->id]),
            'rawMaterials' => $this->presentationService->rawMaterialOptions(
                $ownerType === PresentationService::OWNER_TYPE_RAW_MATERIAL ? [$presentationRecord->raw_material_id] : [],
            ),
            'products' => $this->presentationService->productOptions(
                $ownerType === PresentationService::OWNER_TYPE_PRODUCT ? [$presentationRecord->product_id] : [],
            ),
            'ownerTypes' => PresentationService::ownerTypes(),
        ]);
    }

    public function update(UpdatePresentationRequest $request, string $ownerType, int $presentation): RedirectResponse
    {
        $this->presentationService->update($ownerType, $presentation, $request->validated());

        return to_route('presentations.edit', [
            'ownerType' => $ownerType,
            'presentation' => $presentation,
        ])->with('status', 'Presentacion actualizada correctamente.');
    }

    public function toggleActive(string $ownerType, int $presentation): RedirectResponse
    {
        $updatedPresentation = $this->presentationService->toggleActive($ownerType, $presentation);

        return to_route('presentations.index')
            ->with('status', $updatedPresentation->is_active ? 'Presentacion reactivada correctamente.' : 'Presentacion desactivada correctamente.');
    }

    public function destroy(string $ownerType, int $presentation): RedirectResponse
    {
        $this->presentationService->delete($ownerType, $presentation);

        return to_route('presentations.index')
            ->with('status', 'Presentacion eliminada correctamente.');
    }
}
