<?php

namespace Database\Factories;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Warehouse>
 */
class WarehouseFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Almacen General',
            'Produccion',
            'Materia Prima',
            'Producto Terminado',
        ]);

        return [
            'name' => $name,
            'code' => fake()->unique()->lexify('ALM-???'),
            'type' => fake()->randomElement([
                Warehouse::TYPE_MIXED,
                Warehouse::TYPE_RAW_MATERIAL,
                Warehouse::TYPE_FINISHED_PRODUCT,
            ]),
            'is_active' => true,
        ];
    }
}
