<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Services\ProductService;
use App\Services\SupplierService;
use App\Services\UnitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService,
        private UnitService $unitService,
        private SupplierService $supplierService,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('products/index', [
            'filters' => [
                'search' => $request->string('search')->toString(),
            ],
            'products' => $this->productService->paginateForIndex($request->string('search')->toString()),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('products/create', [
            'units' => $this->unitService->options(),
            'suppliers' => $this->supplierService->options(),
            'supplySources' => [
                Product::SUPPLY_SOURCE_PRODUCTION,
                Product::SUPPLY_SOURCE_SUPPLIER,
                Product::SUPPLY_SOURCE_MIXED,
            ],
            'productTypes' => [
                Product::TYPE_FINISHED,
                Product::TYPE_INTERMEDIATE,
            ],
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = $this->productService->create($request->validated());

        return to_route('products.edit', $product)
            ->with('status', 'Producto creado correctamente.');
    }

    public function edit(Product $product): Response
    {
        $product->load(['baseUnit:id,name,code', 'supplierLinks.supplier:id,name']);

        return Inertia::render('products/edit', [
            'productRecord' => $product,
            'units' => $this->unitService->options([$product->base_unit_id]),
            'suppliers' => $this->supplierService->options($product->supplierLinks->pluck('supplier_id')->filter()->all()),
            'selectedSupplierId' => $product->supplierLinks->first()?->supplier_id,
            'supplySources' => [
                Product::SUPPLY_SOURCE_PRODUCTION,
                Product::SUPPLY_SOURCE_SUPPLIER,
                Product::SUPPLY_SOURCE_MIXED,
            ],
            'productTypes' => [
                Product::TYPE_FINISHED,
                Product::TYPE_INTERMEDIATE,
            ],
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $this->productService->update($product, $request->validated());

        return to_route('products.edit', $product)
            ->with('status', 'Producto actualizado correctamente.');
    }

    public function toggleActive(Product $product): RedirectResponse
    {
        $this->productService->toggleActive($product);

        return to_route('products.index')
            ->with('status', $product->is_active ? 'Producto desactivado correctamente.' : 'Producto reactivado correctamente.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->productService->delete($product);

        return to_route('products.index')
            ->with('status', 'Producto eliminado correctamente.');
    }
}
