<?php

namespace Database\Factories;

use App\Models\FinanceTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinanceTransaction>
 */
class FinanceTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'folio' => 'FIN-'.str_pad((string) fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'transaction_type' => FinanceTransaction::TYPE_EXPENSE,
            'direction' => FinanceTransaction::DIRECTION_OUT,
            'source' => FinanceTransaction::SOURCE_MANUAL,
            'concept' => fake()->sentence(3),
            'detail' => fake()->sentence(),
            'amount' => fake()->randomFloat(2, 10, 5000),
            'status' => FinanceTransaction::STATUS_POSTED,
            'is_manual' => true,
            'affects_balance' => true,
            'created_by' => User::factory(),
            'reference_type' => null,
            'reference_id' => null,
            'occurred_at' => fake()->dateTimeBetween('-30 days'),
            'notes' => fake()->optional()->sentence(),
            'meta' => null,
        ];
    }
}
