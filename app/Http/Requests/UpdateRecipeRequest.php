<?php

namespace App\Http\Requests;

use App\Models\Recipe;
use App\Models\RecipeItem;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateRecipeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('recipes.update') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'name' => ['required', 'string', 'max:255'],
            'version' => ['required', 'integer', 'min:1'],
            'yield_quantity' => ['required', 'numeric', 'gt:0'],
            'yield_unit_id' => ['required', 'integer', 'exists:units,id'],
            'is_active' => ['required', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_type' => ['required', 'string', Rule::in([
                RecipeItem::ITEM_TYPE_RAW_MATERIAL,
                RecipeItem::ITEM_TYPE_PRODUCT,
            ])],
            'items.*.item_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_id' => ['required', 'integer', 'exists:units,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var Recipe $recipe */
            $recipe = $this->route('recipe');
            $productId = (int) $this->input('product_id');
            $version = (int) $this->input('version');

            if (Recipe::query()
                ->where('product_id', $productId)
                ->where('version', $version)
                ->whereKeyNot($recipe->id)
                ->exists()) {
                $validator->errors()->add('version', 'La version ya existe para este producto.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active', true),
        ]);
    }
}
