<?php

namespace App\Http\Controllers;

use App\Services\InventoryMovementService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class InventoryStockController extends Controller
{
    public function __construct(private InventoryMovementService $inventoryMovementService) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $warehouseId = $request->integer('warehouse') ?: null;
        $stockSummary = $this->inventoryMovementService->stockSummary($search, $warehouseId);
        $summaryCollection = collect($stockSummary);

        return Inertia::render('inventory-stocks/index', [
            'filters' => [
                'search' => $search,
                'warehouse' => $request->string('warehouse')->toString(),
            ],
            'warehouses' => $this->inventoryMovementService->warehouseOptions(),
            'stockSummary' => $stockSummary,
            'metrics' => $this->buildMetrics($summaryCollection),
        ]);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $stockSummary
     * @return array<string, int>
     */
    private function buildMetrics(Collection $stockSummary): array
    {
        return [
            'records' => $stockSummary->count(),
            'raw_materials' => $stockSummary->where('item_type', 'raw_material')->count(),
            'products' => $stockSummary->where('item_type', 'product')->count(),
            'warehouses' => $stockSummary
                ->pluck('warehouse.id')
                ->filter(fn (mixed $warehouseId): bool => is_numeric($warehouseId) && (int) $warehouseId > 0)
                ->unique()
                ->count(),
        ];
    }
}
