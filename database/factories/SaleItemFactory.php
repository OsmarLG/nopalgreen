<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductPresentation;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleItem>
 */
class SaleItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->randomFloat(3, 1, 20);
        $unitPrice = fake()->randomFloat(2, 10, 120);
        $product = Product::factory();

        return [
            'sale_id' => Sale::factory(),
            'product_id' => $product,
            'presentation_id' => ProductPresentation::factory()->for($product, 'product'),
            'quantity' => $quantity,
            'sold_quantity' => $quantity,
            'returned_quantity' => 0,
            'catalog_price' => $unitPrice,
            'unit_price' => $unitPrice,
            'discount_total' => 0,
            'line_total' => round($quantity * $unitPrice, 2),
            'discount_note' => null,
        ];
    }
}
