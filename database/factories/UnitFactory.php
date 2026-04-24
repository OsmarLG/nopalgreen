<?php

namespace Database\Factories;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Kilogramo',
            'Gramo',
            'Litro',
            'Mililitro',
            'Pieza',
            'Paquete',
        ]);

        return [
            'name' => $name,
            'code' => Str::lower(Str::substr($name, 0, 3)).fake()->unique()->numberBetween(1, 99),
            'decimal_places' => fake()->randomElement([0, 2, 3]),
            'is_active' => true,
        ];
    }
}
