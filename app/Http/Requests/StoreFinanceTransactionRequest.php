<?php

namespace App\Http\Requests;

use App\Models\FinanceTransaction;
use Illuminate\Foundation\Http\FormRequest;

class StoreFinanceTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('finances.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'transaction_type' => ['required', 'in:'.implode(',', FinanceTransaction::TRANSACTION_TYPES)],
            'concept' => ['required', 'string', 'max:160'],
            'detail' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'status' => ['required', 'in:'.implode(',', FinanceTransaction::STATUSES)],
            'occurred_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
