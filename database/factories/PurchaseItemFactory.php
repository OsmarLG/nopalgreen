<?php

namespace Database\Factories;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\RawMaterial;
use App\Models\RawMaterialPresentation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseItem>
 */
class PurchaseItemFactory extends Factory
{
    public function definition(): array
    {
        $quantity = fake()->randomFloat(3, 1, 25);
        $unitCost = fake()->randomFloat(2, 10, 500);

        return [
            'purchase_id' => Purchase::factory(),
            'item_type' => PurchaseItem::ITEM_TYPE_RAW_MATERIAL,
            'item_id' => RawMaterial::factory(),
            'presentation_type' => PurchaseItem::PRESENTATION_TYPE_RAW_MATERIAL,
            'presentation_id' => RawMaterialPresentation::factory(),
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'total' => round($quantity * $unitCost, 2),
        ];
    }
}
