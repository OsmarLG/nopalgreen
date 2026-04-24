<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['name' => 'Kilogramo', 'code' => 'kg', 'decimal_places' => 3],
            ['name' => 'Gramo', 'code' => 'g', 'decimal_places' => 0],
            ['name' => 'Litro', 'code' => 'l', 'decimal_places' => 3],
            ['name' => 'Mililitro', 'code' => 'ml', 'decimal_places' => 0],
            ['name' => 'Pieza', 'code' => 'pz', 'decimal_places' => 0],
            ['name' => 'Paquete', 'code' => 'paq', 'decimal_places' => 0],
        ];

        foreach ($units as $unit) {
            Unit::query()->updateOrCreate(['code' => $unit['code']], $unit);
        }
    }
}
