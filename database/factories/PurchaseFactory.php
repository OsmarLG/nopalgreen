<?php

namespace Database\Factories;

use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Purchase>
 */
class PurchaseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'folio' => fake()->unique()->bothify('COM-######'),
            'supplier_id' => Supplier::factory(),
            'status' => Purchase::STATUS_DRAFT,
            'purchased_at' => now(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
