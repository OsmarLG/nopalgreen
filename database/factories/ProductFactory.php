<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Tortilla Blanca',
            'Tortilla Amarilla',
            'Totopos',
            'Masa Lista',
            'Tostada',
        ]);

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 999),
            'description' => fake()->sentence(),
            'base_unit_id' => Unit::factory(),
            'supply_source' => fake()->randomElement([
                Product::SUPPLY_SOURCE_PRODUCTION,
                Product::SUPPLY_SOURCE_SUPPLIER,
                Product::SUPPLY_SOURCE_MIXED,
            ]),
            'product_type' => fake()->randomElement([
                Product::TYPE_FINISHED,
                Product::TYPE_INTERMEDIATE,
            ]),
            'sale_price' => fake()->randomFloat(2, 8, 200),
            'is_active' => true,
        ];
    }
}
