<?php

namespace Database\Factories;

use App\Models\RawMaterial;
use App\Models\RawMaterialPresentation;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RawMaterialPresentation>
 */
class RawMaterialPresentationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'raw_material_id' => RawMaterial::factory(),
            'name' => fake()->randomElement(['Costal 25 kg', 'Saco 50 kg', 'Caja 12 pzas']),
            'quantity' => fake()->randomFloat(3, 1, 50),
            'unit_id' => Unit::factory(),
            'barcode' => fake()->optional()->ean13(),
            'is_active' => true,
        ];
    }
}
