<?php

namespace App\Http\Requests;

use App\Models\ProductionOrder;
use App\Models\ProductionOrderConsumption;
use App\Models\Recipe;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreProductionOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('production_orders.create') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'recipe_id' => ['required', 'integer', 'exists:recipes,id'],
            'planned_quantity' => ['required', 'numeric', 'gt:0'],
            'produced_quantity' => ['required', 'numeric', 'gte:0'],
            'unit_id' => ['required', 'integer', 'exists:units,id'],
            'status' => ['required', 'string', Rule::in(ProductionOrder::STATUSES)],
            'scheduled_for' => ['nullable', 'date'],
            'started_at' => ['nullable', 'date'],
            'finished_at' => ['nullable', 'date', 'after_or_equal:started_at'],
            'notes' => ['nullable', 'string'],
            'consumptions' => ['required', 'array', 'min:1'],
            'consumptions.*.item_type' => ['required', 'string', Rule::in([
                ProductionOrderConsumption::ITEM_TYPE_RAW_MATERIAL,
                ProductionOrderConsumption::ITEM_TYPE_PRODUCT,
            ])],
            'consumptions.*.item_id' => ['required', 'integer'],
            'consumptions.*.planned_quantity' => ['required', 'numeric', 'gte:0'],
            'consumptions.*.consumed_quantity' => ['required', 'numeric', 'gte:0'],
            'consumptions.*.unit_id' => ['required', 'integer', 'exists:units,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $recipeId = (int) $this->input('recipe_id');
            $productId = (int) $this->input('product_id');

            if (! Recipe::query()
                ->whereKey($recipeId)
                ->where('product_id', $productId)
                ->exists()) {
                $validator->errors()->add('recipe_id', 'La receta seleccionada no corresponde al producto final.');
            }
        });
    }
}
