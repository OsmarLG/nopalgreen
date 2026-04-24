<?php

namespace Database\Factories;

use App\Models\InventoryMovement;
use App\Models\RawMaterial;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryMovement>
 */
class InventoryMovementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'warehouse_id' => Warehouse::factory(),
            'item_type' => InventoryMovement::ITEM_TYPE_RAW_MATERIAL,
            'item_id' => RawMaterial::factory(),
            'movement_type' => InventoryMovement::TYPE_PURCHASE,
            'direction' => InventoryMovement::DIRECTION_IN,
            'quantity' => fake()->randomFloat(3, 1, 100),
            'unit_cost' => fake()->randomFloat(2, 10, 500),
            'reference_type' => null,
            'reference_id' => null,
            'notes' => fake()->sentence(),
            'moved_at' => now(),
        ];
    }
}
