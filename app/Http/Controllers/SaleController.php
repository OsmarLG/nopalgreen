<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSaleRequest;
use App\Http\Requests\UpdateSaleRequest;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SaleController extends Controller
{
    public function __construct(private SaleService $saleService) {}

    public function index(Request $request): Response
    {
        return Inertia::render('sales/index', [
            'filters' => [
                'search' => $request->string('search')->toString(),
            ],
            'sales' => $this->saleService->paginateForIndex($request->user(), $request->string('search')->toString()),
        ]);
    }

    public function pos(): Response
    {
        return Inertia::render('pos/index', [
            'customers' => $this->saleService->customerOptions(),
            'deliveryUsers' => $this->saleService->deliveryUserOptions(),
            'products' => $this->saleService->productOptions(),
            'saleTypes' => $this->saleService->typeOptions(),
            'statuses' => $this->saleService->statusOptions(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('sales/create', [
            'customers' => $this->saleService->customerOptions(),
            'deliveryUsers' => $this->saleService->deliveryUserOptions(),
            'products' => $this->saleService->productOptions(),
            'saleTypes' => $this->saleService->typeOptions(),
            'statuses' => $this->saleService->statusOptions(),
        ]);
    }

    public function store(StoreSaleRequest $request): RedirectResponse
    {
        $sale = $this->saleService->create($request->validated());

        return to_route('sales.edit', $sale)
            ->with('status', 'Venta creada correctamente.');
    }

    public function storeFromPos(StoreSaleRequest $request): RedirectResponse
    {
        $sale = $this->saleService->create($request->validated());

        return to_route('pos.index')
            ->with('status', "Venta {$sale->folio} registrada correctamente.");
    }

    public function edit(Sale $sale): Response
    {
        abort_unless($this->saleService->canAccess(request()->user(), $sale), 404);

        $sale->load([
            'customer:id,name',
            'deliveryUser:id,name',
            'items.product:id,name',
            'items.presentation:id,product_id,name',
        ]);

        return Inertia::render('sales/edit', [
            'saleRecord' => $this->saleService->formatForEdit($sale),
            'customers' => $this->saleService->customerOptions($sale->customer_id ? [$sale->customer_id] : []),
            'deliveryUsers' => $this->saleService->deliveryUserOptions($sale->delivery_user_id ? [$sale->delivery_user_id] : []),
            'products' => $this->saleService->productOptions(
                $sale->items->pluck('product_id')->filter()->all(),
                $sale->items->pluck('presentation_id')->filter()->all(),
            ),
            'saleTypes' => $this->saleService->typeOptions(),
            'statuses' => $this->saleService->statusOptions(),
        ]);
    }

    public function update(UpdateSaleRequest $request, Sale $sale): RedirectResponse
    {
        abort_unless($this->saleService->canAccess($request->user(), $sale), 404);

        $this->saleService->update($sale, $request->validated());

        return to_route('sales.edit', $sale)
            ->with('status', 'Venta actualizada correctamente.');
    }

    public function destroy(Sale $sale): RedirectResponse
    {
        abort_unless($this->saleService->canAccess(request()->user(), $sale), 404);

        $this->saleService->delete($sale);

        return to_route('sales.index')
            ->with('status', 'Venta eliminada correctamente.');
    }
}
