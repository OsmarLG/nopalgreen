<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInventoryTransferRequest;
use App\Http\Requests\UpdateInventoryTransferRequest;
use App\Models\InventoryTransfer;
use App\Services\InventoryTransferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InventoryTransferController extends Controller
{
    public function __construct(private InventoryTransferService $inventoryTransferService) {}

    public function index(Request $request): Response
    {
        return Inertia::render('inventory-transfers/index', [
            'filters' => [
                'search' => $request->string('search')->toString(),
            ],
            'transfers' => $this->inventoryTransferService->paginateForIndex(
                $request->string('search')->toString(),
            ),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('inventory-transfers/create', [
            'warehouses' => $this->inventoryTransferService->warehouseOptions(),
            'rawMaterials' => $this->inventoryTransferService->rawMaterialOptions(),
            'products' => $this->inventoryTransferService->productOptions(),
            'itemTypes' => $this->inventoryTransferService->itemTypes(),
        ]);
    }

    public function store(StoreInventoryTransferRequest $request): RedirectResponse
    {
        $inventoryTransfer = $this->inventoryTransferService->create($request->validated());

        return to_route('inventory-transfers.edit', $inventoryTransfer)
            ->with('status', 'Transferencia guardada correctamente.');
    }

    public function edit(InventoryTransfer $inventory_transfer): Response
    {
        return Inertia::render('inventory-transfers/edit', [
            'transferRecord' => $this->inventoryTransferService->formatForEdit($inventory_transfer),
            'warehouses' => $this->inventoryTransferService->warehouseOptions([
                $inventory_transfer->source_warehouse_id,
                $inventory_transfer->destination_warehouse_id,
            ]),
            'rawMaterials' => $this->inventoryTransferService->rawMaterialOptions(
                $inventory_transfer->item_type === 'raw_material' ? [$inventory_transfer->item_id] : [],
            ),
            'products' => $this->inventoryTransferService->productOptions(
                $inventory_transfer->item_type === 'product' ? [$inventory_transfer->item_id] : [],
            ),
            'itemTypes' => $this->inventoryTransferService->itemTypes(),
        ]);
    }

    public function update(UpdateInventoryTransferRequest $request, InventoryTransfer $inventory_transfer): RedirectResponse
    {
        $this->inventoryTransferService->update($inventory_transfer, $request->validated());

        return to_route('inventory-transfers.edit', $inventory_transfer)
            ->with('status', 'Transferencia actualizada correctamente.');
    }

    public function destroy(InventoryTransfer $inventory_transfer): RedirectResponse
    {
        $this->inventoryTransferService->delete($inventory_transfer);

        return to_route('inventory-transfers.index')
            ->with('status', 'Transferencia eliminada correctamente.');
    }
}
