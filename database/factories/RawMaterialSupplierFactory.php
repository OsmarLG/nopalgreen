<?php

namespace Database\Factories;

use App\Models\RawMaterial;
use App\Models\RawMaterialSupplier;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RawMaterialSupplier>
 */
class RawMaterialSupplierFactory extends Factory
{
    public function definition(): array
    {
        return [
            'raw_material_id' => RawMaterial::factory(),
            'supplier_id' => Supplier::factory(),
            'supplier_sku' => fake()->bothify('RM-####'),
            'cost' => fake()->randomFloat(2, 10, 1000),
            'is_primary' => true,
        ];
    }
}
