<?php

namespace App\Http\Requests;

use App\Models\Sale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('sales.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'customer_id' => [Rule::requiredIf($this->input('sale_type') === Sale::TYPE_DELIVERY), 'nullable', 'integer', 'exists:customers,id'],
            'delivery_user_id' => [Rule::requiredIf($this->input('sale_type') === Sale::TYPE_DELIVERY), 'nullable', 'integer', 'exists:users,id'],
            'sale_type' => ['required', 'string', Rule::in(Sale::TYPES)],
            'status' => ['required', 'string', Rule::in(Sale::STATUSES)],
            'sale_date' => ['nullable', 'date'],
            'delivery_date' => ['nullable', 'date'],
            'completed_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.presentation_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.sold_quantity' => ['nullable', 'numeric', 'gte:0'],
            'items.*.returned_quantity' => ['nullable', 'numeric', 'gte:0'],
            'items.*.catalog_price' => ['required', 'numeric', 'gte:0'],
            'items.*.unit_price' => ['required', 'numeric', 'gte:0'],
            'items.*.discount_note' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'customer_id' => $this->filled('customer_id') ? (int) $this->input('customer_id') : null,
            'delivery_user_id' => $this->filled('delivery_user_id') ? (int) $this->input('delivery_user_id') : null,
        ]);
    }
}
