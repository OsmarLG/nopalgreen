<?php

namespace Database\Seeders;

use App\Models\RawMaterial;
use App\Models\RawMaterialPresentation;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class RawMaterialPresentationSeeder extends Seeder
{
    public function run(): void
    {
        $kg = Unit::query()->where('code', 'kg')->first();

        if (! $kg) {
            return;
        }

        RawMaterial::query()->get()->each(function (RawMaterial $material) use ($kg): void {
            RawMaterialPresentation::query()->firstOrCreate(
                [
                    'raw_material_id' => $material->id,
                    'name' => 'Presentacion base',
                ],
                [
                    'quantity' => 25,
                    'unit_id' => $kg->id,
                    'barcode' => fake()->ean13(),
                    'is_active' => true,
                ],
            );
        });
    }
}
