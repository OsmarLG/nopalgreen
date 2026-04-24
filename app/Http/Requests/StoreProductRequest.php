<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('products.create') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique(Product::class, 'name')],
            'description' => ['nullable', 'string'],
            'base_unit_id' => ['required', 'integer', 'exists:units,id'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'supply_source' => ['required', 'string', Rule::in([
                Product::SUPPLY_SOURCE_PRODUCTION,
                Product::SUPPLY_SOURCE_SUPPLIER,
                Product::SUPPLY_SOURCE_MIXED,
            ])],
            'product_type' => ['required', 'string', Rule::in([
                Product::TYPE_FINISHED,
                Product::TYPE_INTERMEDIATE,
            ])],
            'sale_price' => ['required', 'numeric', 'gte:0'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active', true),
        ]);
    }
}
