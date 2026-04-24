<?php

namespace Database\Seeders;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\RawMaterial;
use App\Models\RawMaterialPresentation;
use Illuminate\Database\Seeder;

class PurchaseItemSeeder extends Seeder
{
    public function run(): void
    {
        $purchase = Purchase::query()->first();
        $material = RawMaterial::query()->first();
        $presentation = RawMaterialPresentation::query()->where('raw_material_id', $material?->id)->first();

        if (! $purchase || ! $material || ! $presentation) {
            return;
        }

        PurchaseItem::query()->firstOrCreate(
            [
                'purchase_id' => $purchase->id,
                'item_type' => PurchaseItem::ITEM_TYPE_RAW_MATERIAL,
                'item_id' => $material->id,
            ],
            [
                'presentation_type' => PurchaseItem::PRESENTATION_TYPE_RAW_MATERIAL,
                'presentation_id' => $presentation->id,
                'quantity' => 10,
                'unit_cost' => 150,
                'total' => 1500,
            ],
        );
    }
}
