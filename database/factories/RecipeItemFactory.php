<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\RawMaterial;
use App\Models\Recipe;
use App\Models\RecipeItem;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecipeItem>
 */
class RecipeItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'recipe_id' => Recipe::factory(),
            'item_type' => RecipeItem::ITEM_TYPE_RAW_MATERIAL,
            'item_id' => RawMaterial::factory(),
            'quantity' => fake()->randomFloat(3, 0.1, 50),
            'unit_id' => Unit::factory(),
            'sort_order' => 1,
        ];
    }

    public function forProductInput(): static
    {
        return $this->state(fn (): array => [
            'item_type' => RecipeItem::ITEM_TYPE_PRODUCT,
            'item_id' => Product::factory(),
        ]);
    }
}
