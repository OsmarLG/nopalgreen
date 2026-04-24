<?php

namespace Database\Seeders;

use App\Models\RawMaterial;
use App\Models\RawMaterialSupplier;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class RawMaterialSupplierSeeder extends Seeder
{
    public function run(): void
    {
        $supplier = Supplier::query()->first();

        if (! $supplier) {
            return;
        }

        RawMaterial::query()->get()->each(function (RawMaterial $material) use ($supplier): void {
            RawMaterialSupplier::query()->firstOrCreate(
                [
                    'raw_material_id' => $material->id,
                    'supplier_id' => $supplier->id,
                ],
                [
                    'supplier_sku' => 'RM-'.$material->id,
                    'cost' => 100,
                    'is_primary' => true,
                ],
            );
        });
    }
}
