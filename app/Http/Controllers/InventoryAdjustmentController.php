<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInventoryAdjustmentRequest;
use App\Http\Requests\UpdateInventoryAdjustmentRequest;
use App\Models\InventoryMovement;
use App\Services\InventoryAdjustmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InventoryAdjustmentController extends Controller
{
    public function __construct(private InventoryAdjustmentService $inventoryAdjustmentService) {}

    public function index(Request $request): Response
    {
        return Inertia::render('inventory-adjustments/index', [
            'filters' => [
                'search' => $request->string('search')->toString(),
            ],
            'adjustments' => $this->inventoryAdjustmentService->paginateForIndex(
                $request->string('search')->toString(),
            ),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('inventory-adjustments/create', [
            'warehouses' => $this->inventoryAdjustmentService->warehouseOptions(),
            'rawMaterials' => $this->inventoryAdjustmentService->rawMaterialOptions(),
            'products' => $this->inventoryAdjustmentService->productOptions(),
            'itemTypes' => $this->inventoryAdjustmentService->itemTypes(),
            'movementTypes' => $this->inventoryAdjustmentService->manualMovementTypes(),
            'directions' => $this->inventoryAdjustmentService->directions(),
        ]);
    }

    public function store(StoreInventoryAdjustmentRequest $request): RedirectResponse
    {
        $inventoryAdjustment = $this->inventoryAdjustmentService->create($request->validated());

        return to_route('inventory-adjustments.edit', $inventoryAdjustment)
            ->with('status', 'Ajuste guardado correctamente.');
    }

    public function edit(InventoryMovement $inventory_adjustment): Response
    {
        return Inertia::render('inventory-adjustments/edit', [
            'adjustmentRecord' => $this->inventoryAdjustmentService->formatForEdit($inventory_adjustment),
            'warehouses' => $this->inventoryAdjustmentService->warehouseOptions([$inventory_adjustment->warehouse_id]),
            'rawMaterials' => $this->inventoryAdjustmentService->rawMaterialOptions(
                $inventory_adjustment->item_type === InventoryMovement::ITEM_TYPE_RAW_MATERIAL
                    ? [$inventory_adjustment->item_id]
                    : [],
            ),
            'products' => $this->inventoryAdjustmentService->productOptions(
                $inventory_adjustment->item_type === InventoryMovement::ITEM_TYPE_PRODUCT
                    ? [$inventory_adjustment->item_id]
                    : [],
            ),
            'itemTypes' => $this->inventoryAdjustmentService->itemTypes(),
            'movementTypes' => $this->inventoryAdjustmentService->manualMovementTypes(),
            'directions' => $this->inventoryAdjustmentService->directions(),
        ]);
    }

    public function update(UpdateInventoryAdjustmentRequest $request, InventoryMovement $inventory_adjustment): RedirectResponse
    {
        $this->inventoryAdjustmentService->update($inventory_adjustment, $request->validated());

        return to_route('inventory-adjustments.edit', $inventory_adjustment)
            ->with('status', 'Ajuste actualizado correctamente.');
    }

    public function destroy(InventoryMovement $inventory_adjustment): RedirectResponse
    {
        $this->inventoryAdjustmentService->delete($inventory_adjustment);

        return to_route('inventory-adjustments.index')
            ->with('status', 'Ajuste eliminado correctamente.');
    }
}
