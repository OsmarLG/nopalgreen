<?php

namespace App\Http\Requests;

use App\Models\InventoryMovement;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('inventory_adjustments.create') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'item_type' => ['required', 'string', Rule::in([
                InventoryMovement::ITEM_TYPE_RAW_MATERIAL,
                InventoryMovement::ITEM_TYPE_PRODUCT,
            ])],
            'item_id' => ['required', 'integer'],
            'movement_type' => ['required', 'string', Rule::in([
                InventoryMovement::TYPE_ADJUSTMENT,
                InventoryMovement::TYPE_WASTE,
            ])],
            'direction' => ['required', 'string', Rule::in([
                InventoryMovement::DIRECTION_IN,
                InventoryMovement::DIRECTION_OUT,
            ])],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit_cost' => ['nullable', 'numeric', 'gte:0'],
            'moved_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
