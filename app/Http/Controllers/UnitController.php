<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUnitRequest;
use App\Http\Requests\UpdateUnitRequest;
use App\Models\Unit;
use App\Services\UnitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UnitController extends Controller
{
    public function __construct(private UnitService $unitService) {}

    public function index(Request $request): Response
    {
        return Inertia::render('units/index', [
            'filters' => [
                'search' => $request->string('search')->toString(),
            ],
            'units' => $this->unitService->paginateForIndex($request->string('search')->toString()),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('units/create');
    }

    public function store(StoreUnitRequest $request): RedirectResponse
    {
        $unit = $this->unitService->create($request->validated());

        return to_route('units.edit', $unit)
            ->with('status', 'Unidad creada correctamente.');
    }

    public function edit(Unit $unit): Response
    {
        return Inertia::render('units/edit', [
            'unitRecord' => $unit,
        ]);
    }

    public function update(UpdateUnitRequest $request, Unit $unit): RedirectResponse
    {
        $this->unitService->update($unit, $request->validated());

        return to_route('units.edit', $unit)
            ->with('status', 'Unidad actualizada correctamente.');
    }

    public function toggleActive(Unit $unit): RedirectResponse
    {
        $this->unitService->toggleActive($unit);

        return to_route('units.index')
            ->with('status', $unit->is_active ? 'Unidad desactivada correctamente.' : 'Unidad reactivada correctamente.');
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        $this->unitService->delete($unit);

        return to_route('units.index')
            ->with('status', 'Unidad eliminada correctamente.');
    }
}
