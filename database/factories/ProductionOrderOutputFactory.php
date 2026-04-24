<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderOutput;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductionOrderOutput>
 */
class ProductionOrderOutputFactory extends Factory
{
    public function definition(): array
    {
        return [
            'production_order_id' => ProductionOrder::factory(),
            'product_id' => Product::factory(),
            'quantity' => fake()->randomFloat(3, 1, 100),
            'unit_id' => Unit::factory(),
            'is_main_output' => true,
        ];
    }
}
