<?php

namespace Database\Factories;

use App\Models\RawMaterial;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<RawMaterial>
 */
class RawMaterialFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Maiz Blanco',
            'Maiz Amarillo',
            'Harina',
            'Aceite',
            'Sal',
            'Bolsa Transparente',
        ]);

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 999),
            'description' => fake()->sentence(),
            'base_unit_id' => Unit::factory(),
            'is_active' => true,
        ];
    }
}
