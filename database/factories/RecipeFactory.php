<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Recipe;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Recipe>
 */
class RecipeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'name' => 'Formula '.fake()->unique()->word(),
            'version' => 1,
            'yield_quantity' => fake()->randomFloat(3, 1, 100),
            'yield_unit_id' => Unit::factory(),
            'is_active' => true,
        ];
    }
}
