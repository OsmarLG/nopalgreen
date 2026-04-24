<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductPresentation;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductPresentation>
 */
class ProductPresentationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'name' => fake()->randomElement(['Kilogramo', 'Paquete 500 g', 'Bolsa 200 g']),
            'quantity' => fake()->randomFloat(3, 0.2, 5),
            'unit_id' => Unit::factory(),
            'barcode' => fake()->optional()->ean13(),
            'is_active' => true,
        ];
    }
}
