<?php

namespace App\Http\Requests;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('purchases.create') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'status' => ['required', 'string', Rule::in(Purchase::STATUSES)],
            'purchased_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_type' => ['required', 'string', Rule::in([
                PurchaseItem::ITEM_TYPE_RAW_MATERIAL,
                PurchaseItem::ITEM_TYPE_PRODUCT,
            ])],
            'items.*.item_id' => ['required', 'integer'],
            'items.*.presentation_type' => ['required', 'string', Rule::in([
                PurchaseItem::PRESENTATION_TYPE_RAW_MATERIAL,
                PurchaseItem::PRESENTATION_TYPE_PRODUCT,
            ])],
            'items.*.presentation_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_cost' => ['required', 'numeric', 'gte:0'],
            'items.*.total' => ['required', 'numeric', 'gte:0'],
        ];
    }
}
