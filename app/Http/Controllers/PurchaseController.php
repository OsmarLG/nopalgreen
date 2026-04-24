<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseRequest;
use App\Http\Requests\UpdatePurchaseRequest;
use App\Models\Purchase;
use App\Services\PurchaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseController extends Controller
{
    public function __construct(private PurchaseService $purchaseService) {}

    public function index(Request $request): Response
    {
        return Inertia::render('purchases/index', [
            'filters' => [
                'search' => $request->string('search')->toString(),
            ],
            'purchases' => $this->purchaseService->paginateForIndex($request->string('search')->toString()),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('purchases/create', [
            'suppliers' => $this->purchaseService->supplierOptions(),
            'rawMaterials' => $this->purchaseService->rawMaterialOptions(),
            'products' => $this->purchaseService->productOptions(),
            'itemTypes' => $this->purchaseService->itemTypes(),
            'statuses' => $this->purchaseService->statusOptions(),
        ]);
    }

    public function store(StorePurchaseRequest $request): RedirectResponse
    {
        $purchase = $this->purchaseService->create($request->validated());

        return to_route('purchases.edit', $purchase)
            ->with('status', 'Compra creada correctamente.');
    }

    public function edit(Purchase $purchase): Response
    {
        $purchase->load([
            'supplier:id,name',
            'items.rawMaterial:id,name',
            'items.product:id,name',
        ]);

        return Inertia::render('purchases/edit', [
            'purchaseRecord' => $this->purchaseService->formatForEdit($purchase),
            'suppliers' => $this->purchaseService->supplierOptions([$purchase->supplier_id]),
            'rawMaterials' => $this->purchaseService->rawMaterialOptions(
                $purchase->items->where('item_type', 'raw_material')->pluck('item_id')->all(),
                $purchase->items
                    ->where('presentation_type', 'raw_material_presentation')
                    ->pluck('presentation_id')
                    ->filter()
                    ->all(),
            ),
            'products' => $this->purchaseService->productOptions(
                $purchase->items->where('item_type', 'product')->pluck('item_id')->all(),
                $purchase->items
                    ->where('presentation_type', 'product_presentation')
                    ->pluck('presentation_id')
                    ->filter()
                    ->all(),
            ),
            'itemTypes' => $this->purchaseService->itemTypes(),
            'statuses' => $this->purchaseService->statusOptions(),
        ]);
    }

    public function update(UpdatePurchaseRequest $request, Purchase $purchase): RedirectResponse
    {
        $this->purchaseService->update($purchase, $request->validated());

        return to_route('purchases.edit', $purchase)
            ->with('status', 'Compra actualizada correctamente.');
    }

    public function destroy(Purchase $purchase): RedirectResponse
    {
        $this->purchaseService->delete($purchase);

        return to_route('purchases.index')
            ->with('status', 'Compra eliminada correctamente.');
    }
}
