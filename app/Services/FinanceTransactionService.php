<?php

namespace App\Services;

use App\Models\FinanceTransaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class FinanceTransactionService
{
    /**
     * @return LengthAwarePaginator<int, FinanceTransaction>
     */
    public function paginateForIndex(?string $search = null, ?string $type = null, ?string $status = null): LengthAwarePaginator
    {
        return FinanceTransaction::query()
            ->with('creator:id,name')
            ->when($search, function ($query, string $searchTerm): void {
                $query->where(function ($nestedQuery) use ($searchTerm): void {
                    $nestedQuery
                        ->where('folio', 'like', "%{$searchTerm}%")
                        ->orWhere('concept', 'like', "%{$searchTerm}%")
                        ->orWhere('detail', 'like', "%{$searchTerm}%")
                        ->orWhere('source', 'like', "%{$searchTerm}%")
                        ->orWhere('transaction_type', 'like', "%{$searchTerm}%");
                });
            })
            ->when($type, fn ($query, string $selectedType) => $query->where('transaction_type', $selectedType))
            ->when($status, fn ($query, string $selectedStatus) => $query->where('status', $selectedStatus))
            ->latest('occurred_at')
            ->latest('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (FinanceTransaction $transaction): array => [
                'id' => $transaction->id,
                'folio' => $transaction->folio,
                'transaction_type' => $transaction->transaction_type,
                'direction' => $transaction->direction,
                'source' => $transaction->source,
                'concept' => $transaction->concept,
                'detail' => $transaction->detail,
                'amount' => (string) $transaction->amount,
                'status' => $transaction->status,
                'is_manual' => $transaction->is_manual,
                'affects_balance' => $transaction->affects_balance,
                'occurred_at' => $transaction->occurred_at->toDateTimeString(),
                'notes' => $transaction->notes,
                'creator' => $transaction->creator ? [
                    'id' => $transaction->creator->id,
                    'name' => $transaction->creator->name,
                ] : null,
                'can_edit' => $this->canEdit($transaction),
                'can_delete' => $this->canDelete($transaction),
            ]);
    }

    /**
     * @return array{income:string,expense:string,balance:string,debts:string}
     */
    public function summary(?string $search = null, ?string $type = null, ?string $status = null): array
    {
        $query = FinanceTransaction::query()
            ->when($search, function ($builder, string $searchTerm): void {
                $builder->where(function ($nestedQuery) use ($searchTerm): void {
                    $nestedQuery
                        ->where('folio', 'like', "%{$searchTerm}%")
                        ->orWhere('concept', 'like', "%{$searchTerm}%")
                        ->orWhere('detail', 'like', "%{$searchTerm}%");
                });
            })
            ->when($type, fn ($builder, string $selectedType) => $builder->where('transaction_type', $selectedType))
            ->when($status, fn ($builder, string $selectedStatus) => $builder->where('status', $selectedStatus));

        $postedTransactions = (clone $query)
            ->where('status', FinanceTransaction::STATUS_POSTED)
            ->where('affects_balance', true);

        $income = (float) (clone $postedTransactions)
            ->where('direction', FinanceTransaction::DIRECTION_IN)
            ->sum('amount');

        $expense = (float) (clone $postedTransactions)
            ->where('direction', FinanceTransaction::DIRECTION_OUT)
            ->sum('amount');

        $debts = (float) (clone $query)
            ->where('transaction_type', FinanceTransaction::TYPE_DEBT)
            ->where('status', FinanceTransaction::STATUS_PENDING)
            ->sum('amount');

        return [
            'income' => number_format($income, 2, '.', ''),
            'expense' => number_format($expense, 2, '.', ''),
            'balance' => number_format($income - $expense, 2, '.', ''),
            'debts' => number_format($debts, 2, '.', ''),
        ];
    }

    public function create(array $data, int $userId): FinanceTransaction
    {
        return DB::transaction(function () use ($data, $userId): FinanceTransaction {
            return FinanceTransaction::query()->create([
                'folio' => $this->nextFolio(),
                'transaction_type' => $data['transaction_type'],
                ...$this->resolveDerivedValues($data['transaction_type'], $data['status']),
                'source' => FinanceTransaction::SOURCE_MANUAL,
                'concept' => $data['concept'],
                'detail' => $data['detail'] ?? null,
                'amount' => $data['amount'],
                'is_manual' => true,
                'created_by' => $userId,
                'occurred_at' => $data['occurred_at'],
                'notes' => $data['notes'] ?? null,
                'meta' => null,
            ]);
        });
    }

    public function update(FinanceTransaction $transaction, array $data): FinanceTransaction
    {
        $this->ensureManual($transaction);

        $transaction->fill([
            'transaction_type' => $data['transaction_type'],
            ...$this->resolveDerivedValues($data['transaction_type'], $data['status']),
            'concept' => $data['concept'],
            'detail' => $data['detail'] ?? null,
            'amount' => $data['amount'],
            'occurred_at' => $data['occurred_at'],
            'notes' => $data['notes'] ?? null,
        ]);
        $transaction->save();

        return $transaction->refresh();
    }

    public function delete(FinanceTransaction $transaction): void
    {
        $this->ensureManual($transaction);
        $transaction->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function formatForEdit(FinanceTransaction $transaction): array
    {
        $this->ensureManual($transaction);

        return [
            'id' => $transaction->id,
            'transaction_type' => $transaction->transaction_type,
            'concept' => $transaction->concept,
            'detail' => $transaction->detail ?? '',
            'amount' => (string) $transaction->amount,
            'status' => $transaction->status,
            'occurred_at' => $transaction->occurred_at->format('Y-m-d\TH:i'),
            'notes' => $transaction->notes ?? '',
        ];
    }

    /**
     * @return list<string>
     */
    public function typeOptions(): array
    {
        return FinanceTransaction::TRANSACTION_TYPES;
    }

    /**
     * @return list<string>
     */
    public function statusOptions(): array
    {
        return FinanceTransaction::STATUSES;
    }

    public function canEdit(FinanceTransaction $transaction): bool
    {
        return $transaction->is_manual;
    }

    public function canDelete(FinanceTransaction $transaction): bool
    {
        return $transaction->is_manual;
    }

    private function ensureManual(FinanceTransaction $transaction): void
    {
        if (! $transaction->is_manual) {
            throw new ModelNotFoundException('El movimiento financiero automatico no se puede editar manualmente.');
        }
    }

    /**
     * @return array{direction:string,status:string,affects_balance:bool}
     */
    private function resolveDerivedValues(string $type, string $status): array
    {
        return match ($type) {
            FinanceTransaction::TYPE_INCOME,
            FinanceTransaction::TYPE_COLLECTION => [
                'direction' => FinanceTransaction::DIRECTION_IN,
                'status' => $status,
                'affects_balance' => true,
            ],
            FinanceTransaction::TYPE_DEBT => [
                'direction' => FinanceTransaction::DIRECTION_IN,
                'status' => $status,
                'affects_balance' => false,
            ],
            FinanceTransaction::TYPE_EXPENSE,
            FinanceTransaction::TYPE_PAYMENT,
            FinanceTransaction::TYPE_LOSS,
            FinanceTransaction::TYPE_REFUND => [
                'direction' => FinanceTransaction::DIRECTION_OUT,
                'status' => $status,
                'affects_balance' => true,
            ],
            default => [
                'direction' => FinanceTransaction::DIRECTION_OUT,
                'status' => $status,
                'affects_balance' => true,
            ],
        };
    }

    private function nextFolio(): string
    {
        $sequence = FinanceTransaction::query()->count() + 1;

        do {
            $folio = 'FIN-'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
            $sequence++;
        } while (FinanceTransaction::query()->where('folio', $folio)->exists());

        return $folio;
    }
}
