<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRecipeRequest;
use App\Http\Requests\UpdateRecipeRequest;
use App\Models\Recipe;
use App\Services\RecipeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RecipeController extends Controller
{
    public function __construct(private RecipeService $recipeService) {}

    public function index(Request $request): Response
    {
        return Inertia::render('recipes/index', [
            'filters' => [
                'search' => $request->string('search')->toString(),
            ],
            'recipes' => $this->recipeService->paginateForIndex($request->string('search')->toString()),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('recipes/create', [
            'products' => $this->recipeService->productOptions(),
            'rawMaterials' => $this->recipeService->rawMaterialOptions(),
            'units' => $this->recipeService->unitOptions(),
            'itemTypes' => $this->recipeService->itemTypes(),
        ]);
    }

    public function store(StoreRecipeRequest $request): RedirectResponse
    {
        $recipe = $this->recipeService->create($request->validated());

        return to_route('recipes.edit', $recipe)
            ->with('status', 'Receta creada correctamente.');
    }

    public function edit(Recipe $recipe): Response
    {
        $recipe->load([
            'product:id,name',
            'yieldUnit:id,name,code',
            'items.unit:id,name,code',
            'items.rawMaterial:id,name',
            'items.product:id,name',
        ]);

        return Inertia::render('recipes/edit', [
            'recipeRecord' => [
                'id' => $recipe->id,
                'name' => $recipe->name,
                'version' => $recipe->version,
                'yield_quantity' => (string) $recipe->yield_quantity,
                'is_active' => $recipe->is_active,
                'product' => [
                    'id' => $recipe->product->id,
                    'name' => $recipe->product->name,
                ],
                'yield_unit' => [
                    'id' => $recipe->yieldUnit->id,
                    'name' => $recipe->yieldUnit->name,
                    'code' => $recipe->yieldUnit->code,
                ],
                'items' => $recipe->items->sortBy('sort_order')->values()->map(function ($item): array {
                    return [
                        'id' => $item->id,
                        'item_type' => $item->item_type,
                        'item_id' => $item->item_id,
                        'item_name' => $item->item_type === 'raw_material'
                            ? $item->rawMaterial?->name
                            : $item->product?->name,
                        'quantity' => (string) $item->quantity,
                        'unit' => [
                            'id' => $item->unit->id,
                            'name' => $item->unit->name,
                            'code' => $item->unit->code,
                        ],
                        'sort_order' => $item->sort_order,
                    ];
                })->all(),
            ],
            'products' => $this->recipeService->productOptions(
                [$recipe->product_id, ...$recipe->items->where('item_type', 'product')->pluck('item_id')->all()],
            ),
            'rawMaterials' => $this->recipeService->rawMaterialOptions(
                $recipe->items->where('item_type', 'raw_material')->pluck('item_id')->all(),
            ),
            'units' => $this->recipeService->unitOptions([
                $recipe->yield_unit_id,
                ...$recipe->items->pluck('unit.id')->filter()->all(),
            ]),
            'itemTypes' => $this->recipeService->itemTypes(),
        ]);
    }

    public function update(UpdateRecipeRequest $request, Recipe $recipe): RedirectResponse
    {
        $this->recipeService->update($recipe, $request->validated());

        return to_route('recipes.edit', $recipe)
            ->with('status', 'Receta actualizada correctamente.');
    }

    public function toggleActive(Recipe $recipe): RedirectResponse
    {
        $this->recipeService->toggleActive($recipe);

        return to_route('recipes.index')
            ->with('status', $recipe->is_active ? 'Receta desactivada correctamente.' : 'Receta reactivada correctamente.');
    }

    public function destroy(Recipe $recipe): RedirectResponse
    {
        $this->recipeService->delete($recipe);

        return to_route('recipes.index')
            ->with('status', 'Receta eliminada correctamente.');
    }
}
