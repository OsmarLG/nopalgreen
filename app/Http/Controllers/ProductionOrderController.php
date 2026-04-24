<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductionOrderRequest;
use App\Http\Requests\UpdateProductionOrderRequest;
use App\Models\ProductionOrder;
use App\Services\ProductionOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductionOrderController extends Controller
{
    public function __construct(private ProductionOrderService $productionOrderService) {}

    public function index(Request $request): Response
    {
        return Inertia::render('production-orders/index', [
            'filters' => [
                'search' => $request->string('search')->toString(),
            ],
            'productionOrders' => $this->productionOrderService->paginateForIndex($request->string('search')->toString()),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('production-orders/create', [
            'recipes' => $this->productionOrderService->recipeOptions(),
            'statuses' => $this->productionOrderService->statusOptions(),
        ]);
    }

    public function store(StoreProductionOrderRequest $request): RedirectResponse
    {
        $productionOrder = $this->productionOrderService->create($request->validated());

        return to_route('production-orders.edit', $productionOrder)
            ->with('status', 'Orden de produccion creada correctamente.');
    }

    public function edit(ProductionOrder $productionOrder): Response
    {
        $productionOrder->load([
            'product:id,name',
            'recipe:id,name,version,product_id,yield_quantity,yield_unit_id',
            'recipe.product:id,name',
            'recipe.yieldUnit:id,name,code',
            'recipe.items.unit:id,name,code',
            'recipe.items.rawMaterial:id,name',
            'recipe.items.product:id,name',
            'unit:id,name,code',
            'consumptions.unit:id,name,code',
            'consumptions.rawMaterial:id,name',
            'consumptions.product:id,name',
        ]);

        return Inertia::render('production-orders/edit', [
            'productionOrderRecord' => $this->productionOrderService->formatForEdit($productionOrder),
            'recipes' => $this->productionOrderService->recipeOptions([$productionOrder->recipe_id]),
            'statuses' => $this->productionOrderService->statusOptions(),
        ]);
    }

    public function update(UpdateProductionOrderRequest $request, ProductionOrder $productionOrder): RedirectResponse
    {
        $this->productionOrderService->update($productionOrder, $request->validated());

        return to_route('production-orders.edit', $productionOrder)
            ->with('status', 'Orden de produccion actualizada correctamente.');
    }

    public function destroy(ProductionOrder $productionOrder): RedirectResponse
    {
        $this->productionOrderService->delete($productionOrder);

        return to_route('production-orders.index')
            ->with('status', 'Orden de produccion eliminada correctamente.');
    }
}
