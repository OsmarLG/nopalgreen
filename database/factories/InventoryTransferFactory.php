<?php

namespace Database\Factories;

use App\Models\InventoryTransfer;
use App\Models\RawMaterial;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryTransfer>
 */
class InventoryTransferFactory extends Factory
{
    public function definition(): array
    {
        return [
            'source_warehouse_id' => Warehouse::factory(),
            'destination_warehouse_id' => Warehouse::factory(),
            'item_type' => 'raw_material',
            'item_id' => RawMaterial::factory(),
            'quantity' => fake()->randomFloat(3, 1, 50),
            'unit_cost' => fake()->optional()->randomFloat(2, 10, 500),
            'transferred_at' => now(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
