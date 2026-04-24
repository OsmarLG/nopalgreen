<?php

namespace Database\Factories;

use App\Models\ProductionOrder;
use App\Models\ProductionOrderConsumption;
use App\Models\RawMaterial;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductionOrderConsumption>
 */
class ProductionOrderConsumptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'production_order_id' => ProductionOrder::factory(),
            'item_type' => ProductionOrderConsumption::ITEM_TYPE_RAW_MATERIAL,
            'item_id' => RawMaterial::factory(),
            'planned_quantity' => fake()->randomFloat(3, 1, 100),
            'consumed_quantity' => fake()->randomFloat(3, 1, 100),
            'unit_id' => Unit::factory(),
        ];
    }
}
