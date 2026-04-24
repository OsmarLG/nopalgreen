<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFinanceTransactionRequest;
use App\Http\Requests\UpdateFinanceTransactionRequest;
use App\Models\FinanceTransaction;
use App\Services\FinanceTransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FinanceController extends Controller
{
    public function __construct(private FinanceTransactionService $financeTransactionService) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $type = $request->string('type')->toString() ?: null;
        $status = $request->string('status')->toString() ?: null;

        return Inertia::render('finances/index', [
            'filters' => [
                'search' => $search,
                'type' => $type,
                'status' => $status,
            ],
            'summary' => $this->financeTransactionService->summary($search, $type, $status),
            'transactions' => $this->financeTransactionService->paginateForIndex($search, $type, $status),
            'typeOptions' => $this->financeTransactionService->typeOptions(),
            'statusOptions' => $this->financeTransactionService->statusOptions(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('finances/create', [
            'typeOptions' => $this->financeTransactionService->typeOptions(),
            'statusOptions' => $this->financeTransactionService->statusOptions(),
        ]);
    }

    public function store(StoreFinanceTransactionRequest $request): RedirectResponse
    {
        $transaction = $this->financeTransactionService->create($request->validated(), (int) $request->user()->id);

        return to_route('finances.edit', $transaction)
            ->with('status', 'Movimiento financiero guardado correctamente.');
    }

    public function edit(FinanceTransaction $finance): Response
    {
        return Inertia::render('finances/edit', [
            'transactionRecord' => $this->financeTransactionService->formatForEdit($finance),
            'typeOptions' => $this->financeTransactionService->typeOptions(),
            'statusOptions' => $this->financeTransactionService->statusOptions(),
        ]);
    }

    public function update(UpdateFinanceTransactionRequest $request, FinanceTransaction $finance): RedirectResponse
    {
        $this->financeTransactionService->update($finance, $request->validated());

        return to_route('finances.edit', $finance)
            ->with('status', 'Movimiento financiero actualizado correctamente.');
    }

    public function destroy(FinanceTransaction $finance): RedirectResponse
    {
        $this->financeTransactionService->delete($finance);

        return to_route('finances.index')
            ->with('status', 'Movimiento financiero eliminado correctamente.');
    }
}
