<?php

namespace Database\Seeders;

use App\Models\ProductionOrder;
use App\Models\ProductionOrderConsumption;
use App\Models\RecipeItem;
use Illuminate\Database\Seeder;

class ProductionOrderConsumptionSeeder extends Seeder
{
    public function run(): void
    {
        $order = ProductionOrder::query()->first();
        $recipeItem = RecipeItem::query()->first();

        if (! $order || ! $recipeItem) {
            return;
        }

        ProductionOrderConsumption::query()->firstOrCreate(
            [
                'production_order_id' => $order->id,
                'item_type' => $recipeItem->item_type,
                'item_id' => $recipeItem->item_id,
            ],
            [
                'planned_quantity' => $recipeItem->quantity,
                'consumed_quantity' => $recipeItem->quantity,
                'unit_id' => $recipeItem->unit_id,
            ],
        );
    }
}
