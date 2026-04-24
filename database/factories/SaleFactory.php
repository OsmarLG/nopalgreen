<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'folio' => fake()->unique()->bothify('VTA-######'),
            'customer_id' => Customer::factory(),
            'delivery_user_id' => null,
            'sale_type' => Sale::TYPE_DIRECT,
            'status' => Sale::STATUS_DRAFT,
            'sale_date' => now(),
            'delivery_date' => null,
            'completed_at' => null,
            'subtotal' => 0,
            'discount_total' => 0,
            'total' => 0,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
