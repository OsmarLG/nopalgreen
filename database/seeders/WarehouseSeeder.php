<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $warehouses = [
            ['name' => 'Materia Prima', 'code' => 'MAT-001', 'type' => Warehouse::TYPE_RAW_MATERIAL],
            ['name' => 'Producto Terminado', 'code' => 'PROD-001', 'type' => Warehouse::TYPE_FINISHED_PRODUCT],
            ['name' => 'Almacen General', 'code' => 'GEN-001', 'type' => Warehouse::TYPE_MIXED],
        ];

        foreach ($warehouses as $warehouse) {
            Warehouse::query()->updateOrCreate(['code' => $warehouse['code']], [
                ...$warehouse,
                'is_active' => true,
            ]);
        }
    }
}
