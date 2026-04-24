<?php

namespace Database\Factories;

use App\Models\ProductionOrder;
use App\Models\Recipe;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductionOrder>
 */
class ProductionOrderFactory extends Factory
{
    public function definition(): array
    {
        $recipe = Recipe::factory()->create();

        return [
            'folio' => fake()->unique()->bothify('OP-######'),
            'product_id' => $recipe->product_id,
            'recipe_id' => $recipe->id,
            'planned_quantity' => fake()->randomFloat(3, 1, 100),
            'produced_quantity' => 0,
            'unit_id' => $recipe->yield_unit_id,
            'status' => ProductionOrder::STATUS_PLANNED,
            'scheduled_for' => now()->addDay(),
            'started_at' => null,
            'finished_at' => null,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
