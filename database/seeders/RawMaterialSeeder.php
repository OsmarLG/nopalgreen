<?php

namespace Database\Seeders;

use App\Models\RawMaterial;
use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RawMaterialSeeder extends Seeder
{
    public function run(): void
    {
        $kg = Unit::query()->where('code', 'kg')->first();
        $piece = Unit::query()->where('code', 'pz')->first();

        if (! $kg || ! $piece) {
            return;
        }

        $materials = [
            ['name' => 'Maiz Blanco', 'unit' => $kg->id],
            ['name' => 'Maiz Amarillo', 'unit' => $kg->id],
            ['name' => 'Harina', 'unit' => $kg->id],
            ['name' => 'Aceite', 'unit' => $kg->id],
            ['name' => 'Sal', 'unit' => $kg->id],
            ['name' => 'Bolsa Transparente', 'unit' => $piece->id],
        ];

        foreach ($materials as $material) {
            RawMaterial::query()->updateOrCreate(
                ['slug' => Str::slug($material['name'])],
                [
                    'name' => $material['name'],
                    'slug' => Str::slug($material['name']),
                    'description' => 'Materia prima base para produccion e inventario.',
                    'base_unit_id' => $material['unit'],
                    'is_active' => true,
                ],
            );
        }
    }
}
