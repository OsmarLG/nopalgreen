<?php

namespace App\Http\Controllers;

use App\Services\InventoryMovementService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InventoryMovementController extends Controller
{
    public function __construct(private InventoryMovementService $inventoryMovementService) {}

    public function index(Request $request): Response
    {
        return Inertia::render('inventory-movements/index', [
            'filters' => [
                'search' => $request->string('search')->toString(),
                'warehouse' => $request->string('warehouse')->toString(),
            ],
            'warehouses' => $this->inventoryMovementService->warehouseOptions(),
            'movements' => $this->inventoryMovementService->paginateForIndex(
                $request->string('search')->toString(),
                $request->integer('warehouse') ?: null,
            ),
            'stockSummary' => $this->inventoryMovementService->stockSummary(
                $request->string('search')->toString(),
                $request->integer('warehouse') ?: null,
            ),
        ]);
    }
}
